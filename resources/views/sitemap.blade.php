{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('home') }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ route('explore') }}</loc>
        <changefreq>always</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc>{{ route('categories.index') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>

    @foreach ($categories as $category)
        <url>
            <loc>{{ route('categories.show', $category) }}</loc>
            <lastmod>{{ $category->updated_at->toAtomString() }}</lastmod>
            <changefreq>daily</changefreq>
            <priority>0.7</priority>
        </url>
    @endforeach

    @foreach ($products as $product)
        <url>
            <loc>{{ route('products.show', $product) }}</loc>
            <lastmod>{{ $product->updated_at->toAtomString() }}</lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.8</priority>
        </url>
    @endforeach

    @foreach ($sellers as $seller)
        <url>
            <loc>{{ route('sellers.show', $seller) }}</loc>
            <lastmod>{{ $seller->updated_at->toAtomString() }}</lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.6</priority>
        </url>
    @endforeach
</urlset>
