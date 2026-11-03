# Import du référentiel d'entreprises

```bash
docker compose exec app php artisan entreprises:importer database/data/import/mon_fichier.csv --dry-run
docker compose exec app php artisan entreprises:importer database/data/import/mon_fichier.csv --source=artci
docker compose exec app php artisan entreprises:enrichir --limite=100
```

- **Colonnes** (séparateur `;` ou `,`, en-tête obligatoire, seule `nom` est requise) :
  `nom, secteur, site_web, commune, adresse, linkedin_url, taille_estimee, date_creation, description`.
- **secteur** : valeur (`ssii`, `banque_dsi`, `startup`, `telecom`, `integrateur`, `autre`) ou libellé (`SSII / ESN`…). Inconnu → `autre`.
- **Idempotent** : dédoublonnage par slug du nom, puis par domaine du site. Une fiche existante n'est que *complétée* (champs vides) — statut, liste « à éviter » et saisies admin ne sont jamais écrasés.
- Les nouvelles fiches sont **vérifiées** (donc publiques) ; `--a-verifier` les envoie en file de modération.
- **Uniquement des faits publics** (annuaires ARTCI / APBEF / GOTIC, registres, OpenStreetMap…). Jamais d'avis copiés d'autres plateformes.

`exemple_entreprises.csv` est un échantillon : vérifier chaque ligne avant de l'importer en production.
