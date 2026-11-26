import { Controller } from '@hotwired/stimulus';

/*
 * Adventskalender: Fokus in den geöffneten Tür-Dialog setzen (Tastatur, Screenreader) und die Seite dahinter
 * nicht mitscrollen lassen.
 */
export default class extends Controller {
    connect() {
        document.documentElement.classList.add('adv-locked');
        const target = this.element.querySelector('.adv-choice, .adv-sheet__done, .adv-sheet__close');
        requestAnimationFrame(() => target?.focus({ preventScroll: true }));
    }

    disconnect() {
        document.documentElement.classList.remove('adv-locked');
    }
}
