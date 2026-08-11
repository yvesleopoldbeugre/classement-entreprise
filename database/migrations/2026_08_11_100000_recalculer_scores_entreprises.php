<?php

use App\Services\ClassementService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Recalcule les scores dénormalisés de TOUTES les entreprises
     * (moyenne pondérée `note_globale` + `score_bayesien`) avec la moyenne
     * globale actuelle du site. Corrige les notes figées/périmées affichées
     * sur les fiches. Idempotent (recalcul pur, aucune donnée d'avis modifiée).
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
