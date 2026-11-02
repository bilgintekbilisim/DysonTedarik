/**
 * Ürün arama kutusu: yazarken ad / kod / marka önerileri getirir.
 * JavaScript kapalıysa form normal GET isteğiyle /urunler?q= sayfasına gider.
 */
export function searchBox({ endpoint }) {
    return {
        query: '',
        results: [],
        open: false,
        loading: false,
        active: -1,
        controller: null,
        debounce: null,

        onInput() {
            clearTimeout(this.debounce);
            this.debounce = setTimeout(() => this.fetch(), 250);
        },

        async fetch() {
            const term = this.query.trim();

            if (term.length < 2) {
                this.results = [];
                this.open = false;
                return;
            }

            this.controller?.abort();
            this.controller = new AbortController();
            this.loading = true;

            try {
                const response = await fetch(`${endpoint}?q=${encodeURIComponent(term)}`, {
                    headers: { Accept: 'application/json' },
                    signal: this.controller.signal,
                });

                if (!response.ok) return;

                this.results = (await response.json()).data ?? [];
                this.active = -1;
                this.open = true;
            } catch (error) {
                if (error.name !== 'AbortError') this.results = [];
            } finally {
                this.loading = false;
            }
        },

        move(step) {
            if (!this.open || this.results.length === 0) return;
            this.active = (this.active + step + this.results.length) % this.results.length;
        },

        choose(event) {
            if (this.open && this.active >= 0 && this.results[this.active]) {
                event.preventDefault();
                window.location.href = this.results[this.active].url;
            }
        },

        close() {
            this.open = false;
            this.active = -1;
        },
    };
}
