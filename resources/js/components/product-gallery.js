/** Ürün detay galerisi: küçük resimler, ok tuşları ve tam ekran görüntüleme. */
export function productGallery({ count = 1 } = {}) {
    return {
        current: 0,
        count,
        zoomed: false,

        show(index) {
            this.current = (index + this.count) % this.count;
        },

        next() {
            this.show(this.current + 1);
        },

        prev() {
            this.show(this.current - 1);
        },

        open() {
            this.zoomed = true;
            document.body.style.overflow = 'hidden';
        },

        close() {
            this.zoomed = false;
            document.body.style.overflow = '';
        },
    };
}
