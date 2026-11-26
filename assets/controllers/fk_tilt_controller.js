import { Controller } from '@hotwired/stimulus';

/*
 * Fotokalender-Übersicht: Karte kippt leicht mit der Maus, das Bild verschiebt sich gegenläufig (wie auf der alten
 * Seite, jetzt ohne Vue). Nur mit Maus und ohne „Bewegung reduzieren“.
 */
export default class extends Controller {
    static targets = ['card', 'bg'];

    connect() {
        this.enabled = window.matchMedia('(hover: hover) and (pointer: fine)').matches
            && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    move(event) {
        if (!this.enabled || event.pointerType !== 'mouse') return;
        const rect = this.element.getBoundingClientRect();
        const x = (event.clientX - rect.left) / rect.width - 0.5;
        const y = (event.clientY - rect.top) / rect.height - 0.5;
        this.cardTarget.style.transform = `rotateY(${x * 18}deg) rotateX(${y * -18}deg)`;
        if (this.hasBgTarget) {
            this.bgTarget.style.transform = `translate(${x * -24}px, ${y * -24}px) scale(1.12)`;
        }
    }

    reset() {
        this.cardTarget.style.transform = '';
        if (this.hasBgTarget) this.bgTarget.style.transform = '';
    }
}
