<?php

namespace App\Console\Commands;

use App\Enums\SecteurActivite;
use App\Enums\StatutEntreprise;
use App\Models\Entreprise;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Importe un référentiel d'entreprises depuis un CSV (faits publics uniquement,
 * jamais d'avis). Idempotent : dédoublonne par slug du nom puis par domaine du
 * site, et ne complète que les champs vides d'une fiche existante.
 */
class ImporterEntreprises extends Command
{
    protected $signature = 'entreprises:importer
                            {fichier : Chemin du CSV (en-tête : nom, secteur, site_web, commune, adresse, linkedin_url, taille_estimee, date_creation, description)}
                            {--source=import_csv : Valeur de source_scraping pour les nouvelles fiches}
                            {--a-verifier : Crée les nouvelles fiches en « à vérifier » au lieu de « vérifiée »}
                            {--dry-run : Affiche le bilan sans rien écrire}';

    protected $description = 'Importe des entreprises depuis un fichier CSV (idempotent)';

    /** Colonnes CSV reprises telles quelles (après nettoyage). */
    private const CHAMPS = ['adresse', 'commune', 'site_web', 'linkedin_url', 'taille_estimee', 'date_creation', 'description'];

    public function handle(): int
    {
        $chemin = $this->argument('fichier');

        if (! is_readable($chemin)) {
            $this->components->error("Fichier illisible : {$chemin}");

            return self::FAILURE;
        }

        $lignes = $this->lireCsv($chemin);

        if (! $lignes) {
            $this->components->error('CSV vide ou sans colonne « nom ».');

            return self::FAILURE;
        }

        $bilan = ['créées' => 0, 'complétées' => 0, 'inchangées' => 0, 'ignorées' => 0];

        foreach ($lignes as $numero => $ligne) {
            $donnees = $this->normaliser($ligne);

            if ($donnees === null) {
                $this->components->warn("Ligne {$numero} ignorée : nom manquant.");
                $bilan['ignorées']++;

                continue;
            }

            $entreprise = $this->existante($donnees) ?? new Entreprise;
            $nouvelle = ! $entreprise->exists;

            if ($nouvelle) {
                $entreprise->fill($donnees);
                $entreprise->statut = $this->option('a-verifier') ? StatutEntreprise::AVerifier : StatutEntreprise::Verifiee;
                $entreprise->source_scraping = $this->option('source');
            } else {
                // Ne complète que les champs vides : le travail admin n'est jamais écrasé.
                foreach ($donnees as $champ => $valeur) {
                    if (blank($entreprise->getAttribute($champ)) && $champ !== 'nom') {
                        $entreprise->setAttribute($champ, $valeur);
                    }
                }
                if ($entreprise->secteur_activite === SecteurActivite::Autre && isset($donnees['secteur_activite'])) {
                    $entreprise->secteur_activite = $donnees['secteur_activite'];
                }
            }

            $cle = match (true) {
                $nouvelle => 'créées',
                $entreprise->isDirty() => 'complétées',
                default => 'inchangées',
            };
            $bilan[$cle]++;

            if (! $this->option('dry-run')) {
                $entreprise->save();
            }
        }

        $this->components->twoColumnDetail('<fg=gray>Fichier</>', $chemin);
        foreach ($bilan as $libelle => $nombre) {
            $this->components->twoColumnDetail(ucfirst($libelle), (string) $nombre);
        }

        if ($this->option('dry-run')) {
            $this->components->info('Simulation : rien n’a été écrit.');
        }

        return self::SUCCESS;
    }

    /**
     * Lit le CSV (séparateur « , » ou « ; » détecté, BOM Excel toléré).
     *
     * @return array<int, array<string, string>> lignes indexées par numéro de ligne du fichier
     */
    private function lireCsv(string $chemin): array
    {
        $flux = fopen($chemin, 'r');
        $premiere = (string) fgets($flux);
        $separateur = substr_count($premiere, ';') > substr_count($premiere, ',') ? ';' : ',';
        rewind($flux);

        $entetes = fgetcsv($flux, separator: $separateur, escape: '');
        if (! $entetes) {
            return [];
        }
        $entetes = array_map(fn ($e) => mb_strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $e))), $entetes);

        if (! in_array('nom', $entetes, true)) {
            return [];
        }

        $lignes = [];
        $numero = 1;
        while (($valeurs = fgetcsv($flux, separator: $separateur, escape: '')) !== false) {
            $numero++;
            if ($valeurs === [null]) {
                continue; // ligne vide
            }
            $lignes[$numero] = array_combine($entetes, array_pad(array_slice($valeurs, 0, count($entetes)), count($entetes), ''));
        }
        fclose($flux);

        return $lignes;
    }

    /** @return array<string, mixed>|null attributs prêts à remplir, ou null si inexploitable */
    private function normaliser(array $ligne): ?array
    {
        $nom = trim($ligne['nom'] ?? '');
        if ($nom === '') {
            return null;
        }

        $donnees = ['nom' => Str::limit($nom, 250, '')];

        foreach (self::CHAMPS as $champ) {
            $valeur = trim($ligne[$champ] ?? '');
            if ($valeur !== '') {
                $donnees[$champ] = $valeur;
            }
        }

        if (isset($donnees['site_web'])) {
            $donnees['site_web'] = $this->urlPropre($donnees['site_web']);
        }
        if (isset($donnees['linkedin_url'])) {
            $donnees['linkedin_url'] = $this->urlPropre($donnees['linkedin_url']);
        }
        if (isset($donnees['date_creation']) && ! preg_match('/^(19|20)\d{2}$/', $donnees['date_creation'])) {
            unset($donnees['date_creation']);
        }
        $donnees = array_filter($donnees, fn ($v) => $v !== null);

        $donnees['secteur_activite'] = SecteurActivite::depuisSaisie($ligne['secteur'] ?? $ligne['secteur_activite'] ?? null)
            ?? SecteurActivite::Autre;

        return $donnees;
    }

    /** Ajoute le schéma manquant et rejette ce qui n'est pas une URL http(s). */
    private function urlPropre(string $url): ?string
    {
        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? Str::limit(rtrim($url, '/'), 250, '') : null;
    }

    /** Fiche déjà connue : même slug de nom, ou même domaine de site web. */
    private function existante(array $donnees): ?Entreprise
    {
        $parSlug = Entreprise::where('slug', Str::slug($donnees['nom']))->first();
        if ($parSlug || ! isset($donnees['site_web'])) {
            return $parSlug;
        }

        $domaine = $this->domaine($donnees['site_web']);

        return Entreprise::query()
            ->whereNotNull('site_web')
            ->where('site_web', 'like', '%'.$domaine.'%')
            ->get()
            ->first(fn (Entreprise $e) => $this->domaine($e->site_web) === $domaine);
    }

    private function domaine(string $url): string
    {
        return preg_replace('/^www\./', '', mb_strtolower((string) parse_url($url, PHP_URL_HOST)));
    }
}
