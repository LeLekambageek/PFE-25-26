<?php

namespace App\Console\Commands;

use App\Models\Soutenance;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class RappelSoutenances extends Command
{
    protected $signature = 'soutenances:rappel';

    protected $description = "Envoie un rappel aux membres du jury dont la soutenance a lieu dans les 2 prochains jours";

    public function handle(NotificationService $notificationService): int
    {
        $soutenances = Soutenance::with('jury.membre')
            ->where('statut', 'planifiee')
            ->whereBetween('date_soutenance', [now()->startOfDay(), now()->addDays(2)->endOfDay()])
            ->get();

        foreach ($soutenances as $soutenance) {
            foreach ($soutenance->jury as $juryRow) {
                if ($juryRow->membre && $juryRow->estActif()) {
                    $notificationService->rappelAvantSoutenance($soutenance, $juryRow->membre);
                }
            }
        }

        $this->info("Rappels envoyés pour {$soutenances->count()} soutenance(s).");

        return self::SUCCESS;
    }
}
