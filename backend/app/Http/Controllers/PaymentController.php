<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Commission;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Générer une référence de commande unique
     */
    private function generateNumCommande($length = 15)
    {
        return substr(str_shuffle(str_repeat('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', $length)), 0, $length);
    }

    /**
     * Initier un paiement pour une réservation
     */
    public function initiate(Request $request, $bookingId)
    {
        try {
            \Log::info('Payment initiation started', [
                'booking_id' => $bookingId,
                'user_id' => $request->user()?->id,
            ]);

            $booking = Booking::with(['accommodation', 'user'])->findOrFail($bookingId);

            \Log::info('Booking found', [
                'booking_id' => $booking->id,
                'booking_user_id' => $booking->user_id,
                'request_user_id' => $request->user()?->id,
            ]);

            // Vérifier que l'utilisateur est le propriétaire de la réservation
            if ($booking->user_id !== $request->user()->id) {
                \Log::warning('Payment initiation: User not authorized', [
                    'booking_user_id' => $booking->user_id,
                    'request_user_id' => $request->user()->id,
                ]);
                return response()->json(['message' => 'Forbidden'], 403);
            }

            // Vérifier que la réservation n'est pas déjà payée
            if ($booking->isPaid()) {
                \Log::info('Payment initiation: Booking already paid', [
                    'booking_id' => $booking->id,
                    'payment_status' => $booking->payment_status,
                ]);
                return response()->json([
                    'message' => 'Cette réservation est déjà payée',
                    'booking' => $booking
                ], 400);
            }

            // Vérifier que la réservation n'est pas annulée ou expirée
            if ($booking->status === 'cancelled' || $booking->isExpired()) {
                \Log::info('Payment initiation: Booking unavailable', [
                    'booking_id' => $booking->id,
                    'status' => $booking->status,
                    'is_expired' => $booking->isExpired(),
                ]);
                return response()->json([
                    'message' => 'Cette réservation n\'est plus disponible'
                ], 400);
            }

            $paymentMethod = $request->input('payment_method', 'wave-ci');

            $remainingBalance = $booking->remainingBalance();
            if ($remainingBalance <= 0) {
                \Log::info('Payment initiation: No balance remaining', [
                    'booking_id' => $booking->id,
                    'amount_paid' => $booking->amount_paid,
                    'total_price' => $booking->total_price,
                ]);
                return response()->json([
                    'message' => 'Aucun paiement supplémentaire n\'est requis pour cette réservation',
                    'booking' => $booking
                ], 400);
            }

            $depositPaid = $booking->hasPaidDeposit();
            $paymentPurpose = $depositPaid || $booking->deposit_amount <= 0 ? 'balance' : 'deposit';
            if ($paymentPurpose === 'balance' && $remainingBalance <= 0) {
                return response()->json([
                    'message' => 'Le solde de la réservation est déjà réglé'
                ], 400);
            }

            $amountToPay = $paymentPurpose === 'deposit'
                ? min($booking->deposit_amount ?: $remainingBalance, $remainingBalance)
                : $remainingBalance;

            \Log::info('Creating or retrieving payment', [
                'booking_id' => $booking->id,
                'user_id' => $request->user()->id,
                'payment_method' => $paymentMethod,
                'amount' => $amountToPay,
                'purpose' => $paymentPurpose,
            ]);

            $paymentAmount = (int) round($amountToPay);

            $payment = Payment::where('booking_id', $booking->id)
                ->where('user_id', $request->user()->id)
                ->where('purpose', $paymentPurpose)
                ->where('status', 'pending')
                ->first();

            if ($payment) {
                $payment->update([
                    'amount' => $paymentAmount,
                    'payment_method' => $paymentMethod,
                    'payment_reference' => $payment->payment_reference ?? $this->generateNumCommande(),
                    'payment_data' => null,
                ]);
            } else {
                $payment = Payment::create([
                    'booking_id' => $booking->id,
                    'user_id' => $request->user()->id,
                    'amount' => $paymentAmount,
                    'purpose' => $paymentPurpose,
                    'status' => 'pending',
                    'payment_method' => $paymentMethod,
                    'payment_reference' => $this->generateNumCommande(),
                ]);
            }

            \Log::info('Payment created/retrieved', [
                'payment_id' => $payment->id,
                'payment_reference' => $payment->payment_reference,
                'status' => $payment->status,
                'purpose' => $payment->purpose,
                'amount' => $payment->amount,
            ]);

            // Si le paiement existe déjà et est en attente, vérifier s'il a déjà un lien
            if ($payment->isPending() && isset($payment->payment_data['payment_link'])) {
                \Log::info('Payment already has link', [
                    'payment_id' => $payment->id,
                    'payment_link' => $payment->payment_data['payment_link'],
                    'purpose' => $payment->purpose,
                ]);
                return response()->json([
                    'payment' => $payment->load('booking'),
                    'payment_url' => $payment->payment_data['payment_link'],
                    'link' => $payment->payment_data['payment_link']
                ]);
            }

            // Si le paiement a échoué, créer un nouveau paiement avec une nouvelle référence
            if ($payment->isFailed()) {
                \Log::info('Payment failed, creating new reference', [
                    'payment_id' => $payment->id,
                    'old_reference' => $payment->payment_reference,
                ]);
                $payment->update([
                    'status' => 'pending',
                    'amount' => $paymentAmount,
                    'payment_method' => $paymentMethod,
                    'payment_reference' => $this->generateNumCommande(),
                    'payment_data' => null,
                ]);
            }

            // Générer le lien de paiement via l'API malia-pay.com
            \Log::info('Creating payment link via malia-pay API', [
                'payment_id' => $payment->id,
                'payment_method' => $paymentMethod,
                'amount' => $paymentAmount,
            ]);

            try {
                $paymentLink = $this->createPaymentLink($payment, $booking, $request->user(), $paymentMethod);
                
                \Log::info('Payment link created successfully', [
                    'payment_id' => $payment->id,
                    'payment_link' => $paymentLink,
                ]);
                
                // Mettre à jour le paiement avec le lien
                $payment->update([
                    'payment_data' => [
                        'payment_link' => $paymentLink,
                        'method' => $paymentMethod,
                        'created_at' => now()->toIso8601String(),
                    ]
                ]);

                return response()->json([
                    'payment' => $payment->load('booking'),
                    'payment_url' => $paymentLink,
                    'link' => $paymentLink
                ]);
            } catch (\Exception $e) {
                \Log::error('Error creating payment link', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                // Message d'erreur simple pour l'utilisateur
                $userMessage = 'Impossible de créer le lien de paiement. Veuillez réessayer dans quelques instants.';
                
                // Si c'est une erreur spécifique de l'API, utiliser le message de l'API
                if (strpos($e->getMessage(), 'Veuillez') !== false || 
                    strpos($e->getMessage(), 'transaction') !== false ||
                    strpos($e->getMessage(), 'lien') !== false) {
                    $userMessage = $e->getMessage();
                }
                
                return response()->json([
                    'error' => $userMessage
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Payment initiation error', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Message d'erreur simple pour l'utilisateur
            $userMessage = 'Une erreur est survenue lors de l\'initialisation du paiement. Veuillez réessayer.';
            
            return response()->json([
                'error' => $userMessage
            ], 500);
        }
    }

    /**
     * Créer un lien de paiement via l'API malia-pay.com
     */
    private function createPaymentLink($payment, $booking, $user, $paymentMethod)
    {
        \Log::info('createPaymentLink called', [
            'payment_id' => $payment->id,
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'payment_method' => $paymentMethod,
        ]);

        // Mapper les méthodes de paiement vers les channels de malia-pay
        // Les valeurs attendues par l'API sont : OMCI, WAVECI, CARD, DJAMO
        $channelMap = [
            'wave-ci' => 'WAVECI',
            'visa-mastercard' => 'CARD',
            'orange-ci' => 'OMCI',
            'djamo' => 'DJAMO',
        ];

        $channel = $channelMap[$paymentMethod] ?? 'WAVECI';

        \Log::info('Channel mapped', [
            'payment_method' => $paymentMethod,
            'channel' => $channel,
        ]);

        // Séparer le nom complet en prénom et nom
        $fullName = trim($user->name ?? 'Client');
        $nameParts = explode(' ', $fullName, 2);
        $customerName = $nameParts[0] ?? 'Client'; // Prénom
        $customerSurname = $nameParts[1] ?? $nameParts[0] ?? 'Client'; // Nom (ou prénom si un seul mot)

        // Si le nom complet n'a qu'un seul mot, utiliser le même pour prénom et nom
        if (count($nameParts) === 1) {
            $customerSurname = $customerName;
        }

        \Log::info('Customer name parsed', [
            'full_name' => $fullName,
            'customer_name' => $customerName,
            'customer_surname' => $customerSurname,
        ]);

        // Convertir le montant en entier (sans décimales) car l'API Malia-Pay attend des entiers
        // Exemple: 740000.00 devient 740000
        $montant = (int) round($payment->amount);
        
        // S'assurer que c'est bien un entier et non un float
        if (is_float($montant)) {
            $montant = (int) $montant;
        }

        // Formater le numéro de téléphone selon la documentation : "225XXXXXXXXX" (sans le +)
        $phoneNumber = $user->phone ?? '';
        // Retirer le + et les espaces si présents
        $phoneNumber = str_replace(['+', ' ', '-'], '', $phoneNumber);
        // S'assurer que ça commence par 225 (code pays Côte d'Ivoire)
        if (!empty($phoneNumber) && substr($phoneNumber, 0, 3) !== '225') {
            // Si le numéro commence par 0, remplacer par 225
            if (substr($phoneNumber, 0, 1) === '0') {
                $phoneNumber = '225' . substr($phoneNumber, 1);
            } else {
                // Sinon, ajouter 225 au début
                $phoneNumber = '225' . $phoneNumber;
            }
        }

        $data = [
            "montant" => $montant,
            "reference" => $payment->payment_reference,
            "description" => "Paiement de réservation #{$booking->id} - {$booking->accommodation->name}",
            "channel" => $channel,
            "merchant_id" => "MI_AOXBNNUD2J",
            "aggregated_merchant_id" => "am-1j54gkvb820we",
            "customer_name" => $customerName,
            "customer_surname" => $customerSurname,
            "customer_phone_number" => $phoneNumber ?: '22500000000', // Valeur par défaut si vide
            "customer_email" => $user->email,
            "notification_url" => url('/api/payments/webhook'),
            "return_url" => url("/bookings/{$booking->id}?payment=success"),
            "error_url" => url("/bookings/{$booking->id}/payment?error=1"),
            "success_url" => url("/bookings/{$booking->id}?payment=success"),
        ];

        \Log::info('Payment data prepared', [
            'data' => $data,
            'merchant_id' => $data['merchant_id'],
            'aggregated_merchant_id' => $data['aggregated_merchant_id'],
            'phone_formatted' => $phoneNumber,
            'original_phone' => $user->phone ?? 'NOT_SET',
        ]);

        // Encoder en JSON en s'assurant que les nombres sont des entiers (pas de décimales)
        // Utiliser JSON_NUMERIC_CHECK pour forcer les nombres à être des entiers
        $jsonData = json_encode($data, JSON_NUMERIC_CHECK);
        $url = config('services.malia_pay.api_url', 'https://malia-pay.com/api/v1/OnlinePaymentService/add_payer');
        
        // Vérifier que le montant est bien un entier dans le JSON
        $decodedCheck = json_decode($jsonData, true);
        \Log::info('JSON encoding check', [
            'montant_in_data' => $data['montant'],
            'montant_type' => gettype($data['montant']),
            'montant_in_json' => $decodedCheck['montant'] ?? 'NOT_FOUND',
            'montant_type_in_json' => isset($decodedCheck['montant']) ? gettype($decodedCheck['montant']) : 'NOT_FOUND',
        ]);

        \Log::info('Sending request to malia-pay', [
            'url' => $url,
            'json_data' => $jsonData,
        ]);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]);

        $response = curl_exec($curl);
        $curlError = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        \Log::info('Malia-pay API response', [
            'http_code' => $httpCode,
            'response' => $response,
            'response_length' => strlen($response),
            'curl_error' => $curlError,
        ]);
        
        // Log détaillé de la réponse décodée
        $decodedResponse = json_decode($response, true);
        \Log::info('Malia-pay API response decoded', [
            'decoded_response' => $decodedResponse,
            'has_link' => isset($decodedResponse['link']),
            'link_value' => $decodedResponse['link'] ?? 'NOT_SET',
            'status' => $decodedResponse['status'] ?? 'NOT_SET',
            'message' => $decodedResponse['message'] ?? 'NOT_SET',
        ]);

        if ($response === false) {
            \Log::error('cURL error', [
                'error' => $curlError,
            ]);
            throw new \Exception('Erreur cURL: ' . $curlError);
        }

        // Accepter les codes HTTP 200 (OK) et 201 (Created) comme valides
        if ($httpCode !== 200 && $httpCode !== 201) {
            \Log::error('HTTP error from malia-pay', [
                'http_code' => $httpCode,
                'response' => $response,
            ]);
            
            // Essayer de décoder la réponse pour obtenir le message d'erreur
            $errorResp = json_decode($response);
            if (isset($errorResp->message)) {
                throw new \Exception($errorResp->message);
            }
            
            throw new \Exception("Erreur HTTP {$httpCode}: {$response}");
        }

        $resp = json_decode($response);

        if (json_last_error() !== JSON_ERROR_NONE) {
            \Log::error('JSON decode error', [
                'json_error' => json_last_error_msg(),
                'response' => $response,
            ]);
            throw new \Exception('Erreur de décodage JSON: ' . json_last_error_msg() . ' - Réponse: ' . $response);
        }

        // Vérifier si l'API a retourné une erreur
        if (isset($resp->status) && $resp->status === 'error') {
            $errorMessage = $resp->message ?? 'Erreur inconnue de l\'API Malia-Pay';
            \Log::error('Malia-pay API error', [
                'message' => $errorMessage,
                'response' => $response,
            ]);
            throw new \Exception($errorMessage);
        }

        // Vérifier si le lien est présent
        // Le lien peut être dans 'link' ou 'payment_link' ou 'url' ou 'payment_url'
        $paymentLink = $resp->link ?? $resp->payment_link ?? $resp->url ?? $resp->payment_url ?? null;

        // Vérifier aussi dans les données imbriquées si elles existent
        if (!$paymentLink && isset($resp->data)) {
            $paymentLink = $resp->data->link ?? $resp->data->payment_link ?? $resp->data->url ?? null;
        }

        if (!$paymentLink) {
            // Log très détaillé pour comprendre pourquoi le lien est null
            $responseArray = json_decode($response, true);
            \Log::error('Link not found in response - Detailed analysis', [
                'raw_response' => $response,
                'response_length' => strlen($response),
                'http_code' => $httpCode,
                'decoded_object' => $resp,
                'decoded_array' => $responseArray,
                'all_response_keys' => is_object($resp) ? array_keys((array)$resp) : (is_array($responseArray) ? array_keys($responseArray) : 'not an object/array'),
                'link_field_exists' => isset($resp->link),
                'link_value' => $resp->link ?? 'NULL',
                'link_type' => isset($resp->link) ? gettype($resp->link) : 'NOT_SET',
                'status_value' => $resp->status ?? 'NOT_SET',
                'message_value' => $resp->message ?? 'NOT_SET',
            ]);
            
            // Si le statut est success mais le lien est null, c'est peut-être une réponse asynchrone
            // ou l'API utilise un autre mécanisme pour fournir le lien
            if (isset($resp->status) && $resp->status === 'success') {
                // Vérifier s'il y a une référence ou un ID qui pourrait être utilisé pour construire le lien
                $reference = $payment->payment_reference ?? null;
                
                // Essayer de construire le lien manuellement avec la référence
                // Format possible: https://malia-pay.com/pay/{reference} ou similaire
                if ($reference) {
                    // Essayer différents formats de lien possibles
                    $possibleLinks = [
                        "https://malia-pay.com/pay/{$reference}",
                        "https://malia-pay.com/payment/{$reference}",
                        "https://malia-pay.com/checkout/{$reference}",
                        "https://pay.malia-pay.com/{$reference}",
                    ];
                    
                    \Log::warning('Link is null but status is success, trying to construct link', [
                        'reference' => $reference,
                        'possible_links' => $possibleLinks,
                        'api_response' => $response,
                    ]);
                    
                    // Message d'erreur simple pour l'utilisateur
                    throw new \Exception('Impossible d\'obtenir le lien de paiement. Veuillez réessayer dans quelques instants.');
                } else {
                    throw new \Exception('Impossible d\'obtenir le lien de paiement. Veuillez réessayer.');
                }
            }
            
            // Si un message d'erreur est présent, l'utiliser (mais le simplifier si nécessaire)
            if (isset($resp->message)) {
                $apiMessage = $resp->message;
                // Si le message contient des détails techniques, le simplifier
                if (strpos($apiMessage, 'Lien de paiement introuvable') !== false || 
                    strpos($apiMessage, 'réponse API') !== false) {
                    throw new \Exception('Impossible d\'obtenir le lien de paiement. Veuillez réessayer.');
                }
                throw new \Exception($apiMessage);
            }
            
            throw new \Exception('Impossible d\'obtenir le lien de paiement. Veuillez réessayer.');
        }

        \Log::info('Payment link created successfully', [
            'link' => $paymentLink,
            'http_code' => $httpCode,
        ]);

        return $paymentLink;
    }

    /**
     * Rediriger vers le lien de paiement externe
     */
    public function process(Request $request, $paymentId)
    {
        $payment = Payment::with('booking')->findOrFail($paymentId);

        // Vérifier que l'utilisateur est le propriétaire
        if ($payment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Vérifier que le paiement est en attente
        if (!$payment->isPending()) {
            return response()->json([
                'message' => 'Ce paiement a déjà été traité',
                'payment' => $payment
            ], 400);
        }

        // Récupérer le lien de paiement depuis payment_data
        $paymentLink = $payment->payment_data['payment_link'] ?? null;

        if (!$paymentLink) {
            // Si le lien n'existe pas, le régénérer
            try {
                $paymentLink = $this->createPaymentLink(
                    $payment, 
                    $payment->booking, 
                    $payment->user, 
                    $payment->payment_method
                );
                
                $payment->update([
                    'payment_data' => array_merge($payment->payment_data ?? [], [
                        'payment_link' => $paymentLink,
                    ])
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Erreur lors de la génération du lien de paiement: ' . $e->getMessage()
                ], 500);
            }
        }

        return response()->json([
            'payment' => $payment->load('booking'),
            'payment_url' => $paymentLink,
            'link' => $paymentLink,
            'redirect_url' => $paymentLink
        ]);
    }

    /**
     * Webhook pour recevoir les notifications de paiement de malia-pay.com
     */
    public function webhook(Request $request)
    {
        $data = $request->all();
        
        $reference = $data['reference'] ?? null;
        $status = $data['status'] ?? null;
        $transactionId = $data['transactionID'] ?? $data['transaction_id'] ?? null;
        $montant = $data['montant'] ?? $data['amount'] ?? null;
        
        // S'assurer que le montant est un entier
        if ($montant !== null) {
            $montant = (int) round($montant);
        }

        if (!$reference || !$status) {
            \Log::warning('Webhook malia-pay: Référence ou statut manquant', $data);
            return response()->json(['error' => 'Référence ou statut manquant'], 400);
        }

        $payment = Payment::where('payment_reference', $reference)->first();

        if (!$payment) {
            \Log::warning('Webhook malia-pay: Paiement non trouvé', ['reference' => $reference]);
            return response()->json(['error' => 'Paiement non trouvé'], 404);
        }

        return DB::transaction(function () use ($payment, $status, $transactionId, $montant, $data) {
            if ($status === 'Success' || $status === 'success' || $status === 'completed') {
                // Paiement réussi
                $payment->update([
                    'status' => 'completed',
                    'transaction_id' => $transactionId,
                    'amount' => $montant ?? $payment->amount,
                    'paid_at' => now(),
                    'payment_data' => array_merge($payment->payment_data ?? [], [
                        'webhook_data' => $data,
                        'processed_at' => now()->toIso8601String(),
                    ]),
                ]);

                $booking = $this->updateBookingPaymentState($payment);

                if ($booking->payment_status === 'paid') {
                    // Calculer et enregistrer la commission
                    $this->createCommission($payment);
                }

                \Log::info('Webhook malia-pay: Paiement confirmé', [
                    'payment_id' => $payment->id,
                    'reference' => $payment->payment_reference,
                    'transaction_id' => $transactionId
                ]);

                return response()->json([
                    'message' => 'Paiement confirmé avec succès',
                    'payment' => $payment->load('booking')
                ]);
            } else {
                // Paiement échoué
                $payment->update([
                    'status' => 'failed',
                    'payment_data' => array_merge($payment->payment_data ?? [], [
                        'webhook_data' => $data,
                        'failed_at' => now()->toIso8601String(),
                    ]),
                ]);

                $payment->booking->update([
                    'payment_status' => 'failed'
                ]);

                \Log::warning('Webhook malia-pay: Paiement échoué', [
                    'payment_id' => $payment->id,
                    'reference' => $payment->payment_reference,
                    'status' => $status
                ]);

                return response()->json([
                    'message' => 'Paiement échoué',
                    'payment' => $payment->load('booking')
                ], 400);
            }
        });
    }

    /**
     * Callback pour confirmer le paiement (méthode alternative)
     */
    public function callback(Request $request, $paymentId)
    {
        $payment = Payment::with('booking')->findOrFail($paymentId);

        // Vérifier la signature du webhook si nécessaire
        // TODO: Ajouter la vérification de signature pour la sécurité

        $status = $request->input('status'); // 'success', 'failed', etc.

        if ($status === 'success' || $status === 'completed') {
            $payment->update([
                'status' => 'completed',
                'transaction_id' => $request->input('transaction_id', $payment->transaction_id),
                'paid_at' => now(),
                'payment_data' => $request->input('payment_data', []),
            ]);

            $booking = $this->updateBookingPaymentState($payment);

            if ($booking->payment_status === 'paid') {
                // Calculer et enregistrer la commission
                $this->createCommission($payment);
            }

            return response()->json([
                'message' => 'Paiement confirmé',
                'payment' => $payment->load('booking')
            ]);
        } else {
            $payment->update([
                'status' => 'failed',
                'payment_data' => $request->input('payment_data', []),
            ]);

            $payment->booking->update([
                'payment_status' => 'failed'
            ]);

            return response()->json([
                'message' => 'Paiement échoué',
                'payment' => $payment->load('booking')
            ], 400);
        }
    }

    /**
     * Obtenir les détails d'un paiement
     */
    public function show(Request $request, $paymentId)
    {
        $payment = Payment::with(['booking.accommodation', 'user'])->findOrFail($paymentId);

        // Vérifier les permissions
        if ($payment->user_id !== $request->user()->id && 
            $payment->booking->accommodation->host_id !== $request->user()->id &&
            !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($payment);
    }

    private function updateBookingPaymentState(Payment $payment): Booking
    {
        $booking = $payment->booking()->lockForUpdate()->first();

        if (!$booking) {
            throw new \RuntimeException('Réservation introuvable pour ce paiement.');
        }

        $booking->amount_paid = min(
            (float) $booking->total_price,
            (float) $booking->amount_paid + (float) $payment->amount
        );

        if ($payment->purpose === 'deposit' && !$booking->deposit_paid_at) {
            $booking->deposit_paid_at = now();
            if ($booking->status === 'pending') {
                $booking->status = 'confirmed';
            }
        }

        if ($booking->amount_paid >= $booking->total_price) {
            $booking->payment_status = 'paid';
            $booking->expires_at = null;
        } else {
            $booking->payment_status = 'pending';
        }

        $booking->save();

        return $booking;
    }

    /**
     * Créer une commission pour un paiement
     */
    private function createCommission(Payment $payment)
    {
        $booking = $payment->booking;
        $accommodation = $booking->accommodation;

        // Récupérer le taux de commission depuis les settings (défaut: 10%)
        $commissionRate = (float) Setting::get('commission_rate', 10.00);

        $bookingAmount = $booking->total_price;
        $commissionAmount = ($bookingAmount * $commissionRate) / 100;
        $hostAmount = $bookingAmount - $commissionAmount;

        // Vérifier si une commission existe déjà pour ce paiement
        $existingCommission = Commission::where('payment_id', $payment->id)->first();

        if ($existingCommission) {
            // Mettre à jour la commission existante
            $existingCommission->update([
                'booking_amount' => $bookingAmount,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'host_amount' => $hostAmount,
            ]);
        } else {
            // Créer une nouvelle commission
            Commission::create([
                'booking_id' => $booking->id,
                'payment_id' => $payment->id,
                'host_id' => $accommodation->host_id,
                'booking_amount' => $bookingAmount,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'host_amount' => $hostAmount,
                'status' => 'pending',
            ]);
        }
    }
}

