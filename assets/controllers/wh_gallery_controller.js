import { Controller } from '@hotwired/stimulus';

/*
 * Startseite: Die Streifen sind schwarz-weiß, der aktive ist farbig und breiter (wie auf der alten Seite).
 * Maus/Tastatur: Streifen oder Eintrag in der Seitenleiste aktiviert. Handy (ohne Hover): der Streifen, der gerade
 * mitten im Bild ist. Videos laufen nur im aktiven Streifen und nie bei „Bewegung reduzieren“.
 */
export default class extends Controller {
    static targets = ['tile', 'link', 'video'];

    connect() {
        this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        // Gestapelte Streifen (Handy/Tablet, wie in _wh.scss unter 75em): aktiv ist der in der Bildmitte.
        this.stacked = window.matchMedia('(max-width: 74.99em)');
        this.setActive(0);

        if (this.stacked.matches && 'IntersectionObserver' in window) {
            this.observer = new IntersectionObserver((entries) => {
                entries.filter((e) => e.isIntersecting).forEach((e) => this.setActive(this.tileTargets.indexOf(e.target)));
            }, { rootMargin: '-45% 0px -45% 0px' });
            this.tileTargets.forEach((tile) => this.observer.observe(tile));
        }
    }

    disconnect() {
        this.observer?.disconnect();
    }

    activate(event) {
        this.setActive(Number(event.params.index ?? 0));
    }

    setActive(index) {
        if (index < 0) return;
        this.tileTargets.forEach((tile, i) => tile.classList.toggle('is-active', i === index));
        this.linkTargets.forEach((link, i) => link.classList.toggle('is-active', i === index));
        this.videoTargets.forEach((video) => {
            const active = video.closest('.wh-tile')?.classList.contains('is-active');
            if (active && !this.reducedMotion.matches) {
                video.play().catch(() => {});
            } else {
                video.pause();
            }
        });
    }
}
