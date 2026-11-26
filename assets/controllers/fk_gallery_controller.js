import { Controller } from '@hotwired/stimulus';

/*
 * Fotokalender-Monat: Die Bilder liegen in einem Scroll-Snap-Band (Wischen geht ohne JavaScript). Hier kommen
 * Pfeile, Tasten ← →, Vorschaubilder und der Zähler dazu.
 */
export default class extends Controller {
    static targets = ['track', 'slide', 'thumb', 'counter'];

    connect() {
        this.index = 0;
    }

    prev() { this.show(this.index - 1); }

    next() { this.show(this.index + 1); }

    go(event) { this.show(Number(event.params.index)); }

    key(event) {
        if (event.target.closest('input, textarea')) return;
        if (event.key === 'ArrowLeft') { event.preventDefault(); this.prev(); }
        if (event.key === 'ArrowRight') { event.preventDefault(); this.next(); }
    }

    show(index) {
        const count = this.slideTargets.length;
        if (count === 0) return;
        this.index = (index + count) % count;
        const smooth = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.trackTarget.scrollTo({ left: this.slideTargets[this.index].offsetLeft, behavior: smooth ? 'smooth' : 'auto' });
        this.mark();
    }

    sync() {
        clearTimeout(this.syncTimer);
        this.syncTimer = setTimeout(() => {
            const width = this.trackTarget.clientWidth || 1;
            this.index = Math.round(this.trackTarget.scrollLeft / width);
            this.mark();
        }, 60);
    }

    mark() {
        if (this.hasCounterTarget) this.counterTarget.textContent = String(this.index + 1);
        this.thumbTargets.forEach((thumb, i) => {
            if (i === this.index) {
                thumb.setAttribute('aria-current', 'true');
                thumb.scrollIntoView({ block: 'nearest', inline: 'nearest' });
            } else {
                thumb.removeAttribute('aria-current');
            }
        });
    }
}
