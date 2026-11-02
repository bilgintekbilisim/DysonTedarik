{{-- Kurumsal sayfa, kategori, marka ve liste sayfalarının üst başlık alanı. --}}
@props(['title', 'description' => null, 'breadcrumbs' => [], 'image' => null])

<section class="relative isolate overflow-hidden bg-navy-950">
    @if ($image)
        <x-img :path="$image" alt="" :eager="true" class="absolute inset-0 -z-10 size-full object-cover opacity-25" />
    @endif
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_top_right,rgba(249,115,22,0.18),transparent_55%)]" aria-hidden="true"></div>
    <div class="container-site py-10 sm:py-14">
        <x-breadcrumbs :items="$breadcrumbs" :dark="true" class="mb-4" />
        <h1 class="max-w-4xl text-3xl !text-white sm:text-4xl">{{ $title }}</h1>
        @if ($description)
            <p class="mt-4 max-w-3xl text-base text-navy-100 sm:text-lg">{{ $description }}</p>
        @endif
        {{ $slot }}
    </div>
</section>
