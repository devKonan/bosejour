<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    /**
     * Obtenir toutes les méthodes de paiement actives
     */
    public function index()
    {
        $methods = PaymentMethod::active()->ordered()->get();
        return response()->json($methods);
    }

    /**
     * Obtenir une méthode de paiement spécifique
     */
    public function show($id)
    {
        $method = PaymentMethod::findOrFail($id);
        return response()->json($method);
    }
}

