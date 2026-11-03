<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('classement.index') }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
@foreach ($secteurs as $secteur)
    <url>
        <loc>{{ route('classement.secteur', $secteur->slug()) }}</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
@endforeach
@foreach ($communes as $commune)
    <url>
        <loc>{{ route('classement.commune', \Illuminate\Support\Str::slug($commune)) }}</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
@endforeach
@foreach ($entreprises as $entreprise)
    <url>
        <loc>{{ route('entreprises.show', $entreprise->slug) }}</loc>
        <lastmod>{{ $entreprise->updated_at->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
@endforeach
</urlset>
