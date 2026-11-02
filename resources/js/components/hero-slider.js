/**
 * Ana slider. Otomatik geçiş; fare üzerindeyken, sekme gizliyken veya
 * kullanıcı "azaltılmış hareket" tercih ediyorsa durur.
 */
export function heroSlider({ count = 1, autoplay = true, interval = 6000 } = {}) {
    return {
        current: 0,
        count,
        timer: null,
        paused: false,

        init() {
            const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (autoplay && count > 1 && !reduced) {
                this.start();
                document.addEventListener('visibilitychange', () => (document.hidden ? this.stop() : this.start()));
            }
        },

        start() {
            this.stop();
            this.timer = setInterval(() => {
                if (!this.paused) this.next();
            }, interval);
        },

        stop() {
            clearInterval(this.timer);
        },

        go(index) {
            this.current = (index + this.count) % this.count;
        },

        next() {
            this.go(this.current + 1);
        },

        prev() {
            this.go(this.current - 1);
        },
    };
}
