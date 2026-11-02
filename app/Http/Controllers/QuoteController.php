<?php

namespace App\Http\Controllers;

use App\Actions\SubmitInquiry;
use App\Http\Requests\StoreQuoteRequest;
use App\Models\Product;
use App\Support\Seo;
use App\Support\SpamGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuoteController extends Controller
{
    /**
     * ?urun=KOD ile gelinirse ürün adı ve kodu forma önceden doldurulur.
     * &secenek=N ile ebat tablosundaki satır (tip, ebat, artikel) ürün adına eklenir.
     */
    public function create(Request $request): View
    {
        $product = $request->filled('urun')
            ? Product::query()->active()->with('coverImage')->where('code', (string) $request->query('urun'))->first()
            : null;

        $productName = $product?->name;
        $row = $product?->variant_table['rows'][(int) $request->query('secenek', -1)] ?? null;

        if ($row && $request->has('secenek')) {
            $headers = $product->variant_table['headers'];
            $parts = collect($row)
                ->map(fn ($cell, $i) => ['header' => $headers[$i] ?? '', 'value' => trim((string) $cell)])
                ->reject(fn ($c) => $c['value'] === '' || $c['value'] === '-' || str_contains(mb_strtolower($c['header']), 'miktar'))
                ->map(fn ($c) => preg_match('/artikel/i', $c['header']) ? "Artikel {$c['value']}" : $c['value']);

            $productName = mb_substr($product->name.' — '.$parts->implode(' · '), 0, 255);
        }

        $seo = (new Seo(title: 'Teklif İste', description: 'Ürün ve miktar bilgisiyle teklif talebinizi iletin; ekibimiz size dönüş yapsın.'))
            ->crumb('Ana Sayfa', route('home'))
            ->crumb('Teklif İste');
        // Ürüne özel ön doldurulmuş adresler ayrı sayfa olarak dizine girmesin.
        $seo->canonical = route('quote.create');

        return view('quote', compact('product', 'productName', 'seo'));
    }

    public function store(StoreQuoteRequest $request, SubmitInquiry $submit): RedirectResponse
    {
        if (! SpamGuard::isSpam($request)) {
            $submit->quote($request->validated(), $request);
        }

        return redirect()->route('quote.create')->with('form_success', 'quote');
    }
}
