<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Lit la page d'accueil d'un site d'entreprise et en extrait des faits publics
 * (nom, description, page LinkedIn). Best-effort : lève une RuntimeException
 * avec un message lisible si le site est injoignable.
 */
class SiteWebInspecteur
{
    /**
     * @return array{nom: ?string, description: ?string, linkedin_url: ?string}
     *
     * @throws RuntimeException
     */
    public function inspecter(string $url): array
    {
        try {
            $reponse = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; NoteTaBoiteBot/1.0)'])
                ->get($url);
        } catch (\Throwable) {
            throw new RuntimeException('Impossible de contacter ce site.');
        }

        if (! $reponse->successful()) {
            throw new RuntimeException('Le site est injoignable (code '.$reponse->status().').');
        }

        $html = $reponse->body();

        $nom = $this->metaContenu($html, 'property', 'og:site_name')
            ?? (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $m) ? trim(html_entity_decode($m[1], ENT_QUOTES)) : null);
        // Retire les suffixes usuels du <title> (« Nom | Accueil », « Nom - Site officiel »…).
        $nom = $nom ? trim(preg_split('/\s*[|\-–—:·]\s*/u', $nom)[0]) : null;

        $description = $this->metaContenu($html, 'name', 'description')
            ?? $this->metaContenu($html, 'property', 'og:description');

        // Page LinkedIn retenue seulement si elle est la seule citée : un site qui en liste
        // plusieurs (clients, partenaires) ne permet pas de savoir laquelle est la sienne.
        preg_match_all('#https?://(?:[a-z]{2,3}\.)?linkedin\.com/company/([A-Za-z0-9_%\-]+)#i', $html, $l);
        $pages = array_unique(array_map('strtolower', $l[1]));
        $linkedin = count($pages) === 1 ? $l[0][0] : null;

        return [
            'nom' => $nom ?: null,
            'description' => $description,
            'linkedin_url' => $linkedin,
        ];
    }

    /** Extrait le `content` d'une balise `<meta>` repérée par un attribut (ordre indifférent). */
    private function metaContenu(string $html, string $cle, string $valeur): ?string
    {
        if (preg_match('/<meta[^>]+'.$cle.'=["\']'.preg_quote($valeur, '/').'["\'][^>]*>/i', $html, $tag)
            && preg_match('/content=(["\'])(.*?)\1/is', $tag[0], $c)) {
            return trim(html_entity_decode($c[2], ENT_QUOTES)) ?: null;
        }

        return null;
    }
}
