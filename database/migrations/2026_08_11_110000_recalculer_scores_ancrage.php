<?php

use App\Services\ClassementService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Recalcule tous les scores après l'ancrage de la moyenne globale C
     * (blend vers la valeur neutre) : les scores gonflés par le démarrage à froid
     * (ex. 5/5 pour un unique avis) sont ramenés à une valeur réaliste.
     */
    public function up(): void
    {
        app(ClassementService::class)->recalculerTout();
    }

    public function down(): void
    {
        // Recalcul de valeurs dérivées : rien à annuler.
    }
};
