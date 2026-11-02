<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchSuggestionController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/urunler', [CatalogController::class, 'index'])->name('products.index');
Route::get('/kategori/{category:slug}', [CatalogController::class, 'category'])->name('categories.show');
Route::get('/urun/{product:slug}', ProductController::class)->name('products.show');
Route::get('/arama/oneriler', SearchSuggestionController::class)
    ->middleware('throttle:60,1')
    ->name('search.suggest');

Route::get('/markalar', [BrandController::class, 'index'])->name('brands.index');
Route::get('/marka/{brand:slug}', [BrandController::class, 'show'])->name('brands.show');

Route::get('/iletisim', ContactController::class)->name('contact');

// Kurumsal sayfalar en sonda: /hakkimizda, /kvkk-aydinlatma-metni …
Route::get('/{page:slug}', PageController::class)
    ->where('page', '[a-z0-9-]+')
    ->name('pages.show');
