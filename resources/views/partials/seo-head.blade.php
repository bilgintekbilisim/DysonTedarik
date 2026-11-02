@php
    $title = $seo->fullTitle();
    $description = $seo->metaDescription();
    $image = $seo->imageUrl();
    $canonical = $seo->canonicalUrl();
@endphp
<title>{{ $title }}</title>
@if ($description)
    <meta name="description" content="{{ $description }}">
@endif
<meta name="robots" content="{{ $seo->noindex ? 'noindex, follow' : 'index, follow, max-image-preview:large' }}">
<link rel="canonical" href="{{ $canonical }}">

<meta property="og:locale" content="tr_TR">
<meta property="og:type" content="{{ $seo->type === 'product' ? 'product' : ($seo->type === 'article' ? 'article' : 'website') }}">
<meta property="og:site_name" content="{{ $site->get('general.site_name', config('app.name')) }}">
<meta property="og:title" content="{{ $seo->title ?: $title }}">
@if ($description)
    <meta property="og:description" content="{{ $description }}">
@endif
<meta property="og:url" content="{{ $canonical }}">
@if ($image)
    <meta property="og:image" content="{{ $image }}">
    <meta name="twitter:card" content="summary_large_image">
@else
    <meta name="twitter:card" content="summary">
@endif

@if ($verification = $site->get('integrations.google_site_verification'))
    <meta name="google-site-verification" content="{{ $verification }}">
@endif
@if ($bing = $site->get('integrations.bing_site_verification'))
    <meta name="msvalidate.01" content="{{ $bing }}">
@endif

@foreach ($seo->jsonLd() as $schema)
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endforeach
