<div class="mt-5 hidden justify-end gap-2 sm:flex">
    <button type="button" @click="move(-1)" :disabled="atStart" class="flex size-10 items-center justify-center rounded-full border border-slate-300 text-navy-900 hover:bg-navy-50 disabled:opacity-30" aria-label="Önceki">
        <x-heroicon-m-chevron-left class="size-5" aria-hidden="true" />
    </button>
    <button type="button" @click="move(1)" :disabled="atEnd" class="flex size-10 items-center justify-center rounded-full border border-slate-300 text-navy-900 hover:bg-navy-50 disabled:opacity-30" aria-label="Sonraki">
        <x-heroicon-m-chevron-right class="size-5" aria-hidden="true" />
    </button>
</div>
