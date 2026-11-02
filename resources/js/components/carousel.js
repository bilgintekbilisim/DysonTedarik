/** CSS scroll-snap üzerine ileri/geri butonları; kütüphane gerektirmez. */
export function carousel() {
    return {
        atStart: true,
        atEnd: false,

        init() {
            this.update();
            this.$refs.track.addEventListener('scroll', () => this.update(), { passive: true });
            window.addEventListener('resize', () => this.update(), { passive: true });
        },

        update() {
            const track = this.$refs.track;
            this.atStart = track.scrollLeft <= 4;
            this.atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
        },

        move(direction) {
            const track = this.$refs.track;
            track.scrollBy({ left: direction * track.clientWidth * 0.85, behavior: 'smooth' });
        },
    };
}
