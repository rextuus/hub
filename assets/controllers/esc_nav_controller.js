import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["participant", "currentCount", "selector"];

    static values = {
        participantsCount: Number
    }

    currentIndex = 0;

    connect() {
        const urlParams = new URLSearchParams(window.location.search);
        const indexParam = urlParams.get('index');

        if (indexParam !== null) {
            this.currentIndex = parseInt(indexParam);
        } else {
            this.currentIndex = parseInt(sessionStorage.getItem('esc_note_index') || "0");
        }

        if (this.currentIndex >= this.participantsCountValue || this.currentIndex < 0) {
            this.currentIndex = 0;
        }
        this.showParticipant(this.currentIndex);
    }

    showParticipant(index) {
        this.participantTargets.forEach((el, i) => {
            el.classList.toggle('d-none', i !== index);
        });
        this.currentIndex = index;
        sessionStorage.setItem('esc_note_index', index.toString());
        if (this.hasCurrentCountTarget) {
            this.currentCountTarget.textContent = (this.currentIndex + 1).toString();
        }
        if (this.hasSelectorTarget) {
            this.selectorTarget.value = this.currentIndex;
        }
    }

    jump(event) {
        this.showParticipant(parseInt(event.target.value));
    }

    next() {
        if (this.currentIndex < this.participantsCountValue - 1) {
            this.showParticipant(this.currentIndex + 1);
        }
    }

    previous() {
        if (this.currentIndex > 0) {
            this.showParticipant(this.currentIndex - 1);
        }
    }
}
