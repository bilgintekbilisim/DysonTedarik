<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use App\Support\Seo;
use Illuminate\View\View;

class ECatalogController extends Controller
{
    public function __invoke(): View
    {
        $catalogs = Catalog::query()->active()->with('brand')
            ->orderBy('sort_order')
            ->latest()
            ->get();

        $seo = (new Seo(
            title: 'E-Katalog',
            description: 'Ürün kataloglarımızı ve broşürlerimizi PDF olarak görüntüleyin veya indirin.',
        ))->crumb('Ana Sayfa', route('home'))->crumb('E-Katalog');

        return view('e-catalogs.index', compact('catalogs', 'seo'));
    }
}
