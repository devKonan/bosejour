<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ViewPaymentLogs extends Command
{
    protected $signature = 'payment:logs {--lines=50 : Number of lines to display}';
    protected $description = 'Afficher les logs de paiement récents';

    public function handle()
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (!File::exists($logPath)) {
            $this->error('Le fichier de log n\'existe pas.');
            return 1;
        }

        $lines = (int) $this->option('lines');
        
        // Lire les dernières lignes du fichier
        $content = File::get($logPath);
        $allLines = explode("\n", $content);
        $recentLines = array_slice($allLines, -$lines);
        
        // Filtrer les lignes liées aux paiements
        $paymentLines = array_filter($recentLines, function($line) {
            return stripos($line, 'payment') !== false || 
                   stripos($line, 'initiate') !== false || 
                   stripos($line, 'malia') !== false ||
                   stripos($line, 'ERROR') !== false ||
                   stripos($line, 'WARNING') !== false;
        });

        if (empty($paymentLines)) {
            $this->info('Aucun log de paiement récent trouvé.');
            $this->info('Essayez d\'augmenter le nombre de lignes avec --lines=200');
            return 0;
        }

        $this->info("=== Derniers logs de paiement (dernières {$lines} lignes) ===");
        $this->line('');
        
        foreach ($paymentLines as $line) {
            if (stripos($line, 'ERROR') !== false) {
                $this->error($line);
            } elseif (stripos($line, 'WARNING') !== false) {
                $this->warn($line);
            } else {
                $this->line($line);
            }
        }

        return 0;
    }
}

