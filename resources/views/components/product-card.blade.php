@props(['product'])

<article {{ $attributes->merge(['class' => 'group overflow-hidden rounded-2xl border border-stone-200 bg-white transition hover:-translate-y-0.5 hover:border-stone-300 hover:shadow-sm']) }}>
    <a href="{{ route('products.show', $product) }}" class="block focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">
        <div class="relative aspect-[4/3] overflow-hidden bg-stone-100">
            @if ($product->isBoosted())
                <span class="absolute left-3 top-3 z-10 rounded-full bg-emerald-700 px-3 py-1 text-xs font-bold tracking-wide text-white">BOOSTED</span>
            @endif
            @if ($product->primaryImage)
                <img src="{{ Storage::disk($product->primaryImage->disk)->url($product->primaryImage->path) }}" alt="{{ $product->primaryImage->alt_text ?? $product->name }}" class="size-full object-cover transition duration-300 group-hover:scale-[1.02]" loading="lazy">
            @else
                <div class="flex size-full items-center justify-center text-sm text-stone-400">Belum ada foto</div>
            @endif
        </div>
        <div class="p-4 sm:p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-stone-500">{{ $product->category->name }}</p>
            <h3 class="mt-1 line-clamp-2 min-h-12 text-base font-semibold text-stone-950">{{ $product->name }}</h3>
            <p class="mt-3 text-xl font-semibold text-stone-950">Rp{{ number_format($product->price, 0, ',', '.') }}</p>
            <div class="mt-3 flex items-center justify-between gap-3 text-sm text-stone-500">
                <span>{{ $product->condition->label() }}</span>
                <span class="truncate">{{ $product->location }}</span>
            </div>
            <p class="mt-3 truncate text-sm text-stone-600">{{ $product->seller->name }}</p>
        </div>
    </a>
</article>
