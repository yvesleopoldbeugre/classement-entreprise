<?php

namespace App\Http\Controllers;

use App\Enums\SecteurActivite;
use App\Enums\StatutEntreprise;
use App\Http\Requests\Entreprise\ProposerEntrepriseRequest;
use App\Models\Entreprise;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class EntrepriseController extends Controller
{
    public function create(): View
    {
        return view('entreprises.creer', ['secteurs' => SecteurActivite::cases()]);
    }

    /** Pré-remplissage : récupère le nom + la description depuis le site web (best-effort). */
    public function depuisSite(Request $request): JsonResponse
    {
        $request->validate(['url' => ['required', 'url', 'max:255']]);
        $url = $request->string('url')->toString();

        try {
            $reponse = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; NoteTaBoiteBot/1.0)'])
                ->get($url);
        } catch (\Throwable) {
            return response()->json(['message' => 'Impossible de contacter ce site.'], 422);
        }

        if (! $reponse->successful()) {
            return response()->json(['message' => 'Le site est injoignable (code '.$reponse->status().').'], 422);
        }

        $html = $reponse->body();

        $nom = $this->metaContenu($html, 'property', 'og:site_name')
            ?? (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $m) ? trim(html_entity_decode($m[1], ENT_QUOTES)) : null);
        // Retire les suffixes usuels du <title> (« Nom | Accueil », « Nom - Site officiel »…).
        $nom = $nom ? trim(preg_split('/\s*[|\-–—:·]\s*/u', $nom)[0]) : null;

        $description = $this->metaContenu($html, 'name', 'description')
            ?? $this->metaContenu($html, 'property', 'og:description');

        return response()->json([
            'nom' => $nom,
            'commentaire' => $description,
            'site_web' => $url,
        ]);
    }

    /** Extrait le `content` d'une balise `<meta>` repérée par un attribut (ordre indifférent). */
    private function metaContenu(string $html, string $cle, string $valeur): ?string
    {
        if (preg_match('/<meta[^>]+'.$cle.'=["\']'.preg_quote($valeur, '/').'["\'][^>]*>/i', $html, $tag)
            && preg_match('/content=["\']([^"\']*)["\']/i', $tag[0], $c)) {
            return trim(html_entity_decode($c[1], ENT_QUOTES)) ?: null;
        }

        return null;
    }

    public function store(ProposerEntrepriseRequest $request): RedirectResponse
    {
        // Un admin ajoute une entreprise directement vérifiée ; un utilisateur
        // la propose en attente de vérification. Le statut n'est jamais pris
        // depuis le formulaire (on l'écrase).
        $estAdmin = $request->user()->can('moderer');

        $entreprise = Entreprise::create([
            ...$request->validated(),
            'statut' => $estAdmin ? StatutEntreprise::Verifiee : StatutEntreprise::AVerifier,
            'source_scraping' => 'utilisateur',
        ]);

        // Admin → fiche (elle est publiée) ; utilisateur → retour au classement
        // (sa proposition est en attente de vérification, pas encore publique).
        if ($estAdmin) {
            return redirect()->route('entreprises.show', $entreprise)
                ->with('success', 'Entreprise ajoutée et vérifiée.');
        }

        return redirect()->route('classement.index')
            ->with('success', 'Merci ! Votre proposition sera vérifiée par un modérateur avant d’apparaître.');
    }
}
