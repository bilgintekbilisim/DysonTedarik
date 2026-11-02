<div id="iletisim-formu" class="relative scroll-mt-6 rounded-2xl border border-slate-200 p-6 sm:p-8">
    <h2 class="mb-6 text-xl">Bize yazın</h2>

    <x-form.status form="contact" success="Mesajınız alındı. En kısa sürede size dönüş yapacağız." />

    <form method="post" action="{{ route('contact.store') }}" class="space-y-5" novalidate>
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.field name="full_name" label="Ad soyad" :required="true" maxlength="100" autocomplete="name" />
            <x-form.field name="email" label="E-posta" type="email" :required="true" maxlength="150" autocomplete="email" />
            <x-form.field name="phone" label="Telefon" type="tel" maxlength="20" autocomplete="tel" />
            <x-form.field name="subject" label="Konu" maxlength="150" />
        </div>
        <x-form.field name="message" label="Mesaj" :rows="5" :required="true" maxlength="3000" />

        <x-form.protection />

        <button type="submit" class="btn-dark">
            <x-heroicon-o-paper-airplane class="size-4" aria-hidden="true" /> Mesajı gönder
        </button>
    </form>
</div>
