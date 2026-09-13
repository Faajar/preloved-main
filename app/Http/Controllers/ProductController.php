<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        $product = Product::query()
            ->publiclyVisible()
            ->withActiveBoost()
            ->with([
                'category:id,name,slug',
                'seller:id,name,whatsapp_number',
                'images:id,product_id,disk,path,alt_text,sort_order,is_primary',
            ])
            ->whereKey($product->id)
            ->firstOrFail();

        $whatsappUrl = $this->whatsappUrl($product);

        return view('products.show', compact('product', 'whatsappUrl'));
    }

    private function whatsappUrl(Product $product): ?string
    {
        $number = $product->seller->whatsapp_number;

        if (! is_string($number) || preg_match('/\A628[1-9][0-9]{7,10}\z/', $number) !== 1) {
            return null;
        }

        $message = "Halo, saya tertarik dengan barang {$product->name} di Preloved IN. Apakah barangnya masih tersedia? ".route('products.show', $product);

        return 'https://wa.me/'.$number.'?'.http_build_query(
            ['text' => $message],
            encoding_type: PHP_QUERY_RFC3986,
        );
    }
}
