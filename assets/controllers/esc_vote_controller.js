import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['slot', 'slotContent', 'input', 'removeButton', 'country', 'submitButton'];
    static values = {
        initialChoices: Object,
        updateUrl: String
    };

    connect() {
        this.choices = { ...this.initialChoicesValue };
        this.pendingCountryId = null;
        this.updateUI();
    }

    dragStart(event) {
        event.dataTransfer.setData('countryId', event.currentTarget.dataset.countryId);
        event.dataTransfer.effectAllowed = 'move';
    }

    allowDrop(event) {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
        event.currentTarget.classList.add('drag-over');
    }

    drop(event) {
        event.preventDefault();
        event.currentTarget.classList.remove('drag-over');

        const countryId = event.dataTransfer.getData('countryId');
        const points = event.currentTarget.dataset.points;

        if (countryId && points) {
            this.selectCountry(points, countryId);
        }
    }

    addToFirstEmptySlot(event) {
        const countryId = event.currentTarget.dataset.countryId;

        // If already selected, do nothing
        if (Object.values(this.choices).includes(countryId)) {
            return;
        }

        // Find first empty slot
        for (const slot of this.slotTargets) {
            const points = slot.dataset.points;
            if (!this.choices[points]) {
                this.selectCountry(points, countryId);
                return;
            }
        }

        // If no empty slot, maybe show a hint? For now just do nothing
    }

    selectSlot(event) {
        const slot = event.currentTarget;
        const points = slot.dataset.points;

        // If there's a selected but not assigned country, assign it here
        if (this.pendingCountryId) {
            this.selectCountry(points, this.pendingCountryId);
            this.pendingCountryId = null;
            this.updateUI();
        } else if (this.choices[points]) {
            // If slot is filled, "pick it up" (make it pending and clear slot)
            this.pendingCountryId = this.choices[points];
            this.clearSlot(points);
            this.updateUI();
        }
    }

    toggleCountry(event) {
        const countryItem = event.currentTarget;
        const countryId = countryItem.dataset.countryId;

        // If a country is already selected for a field, it should not be possible to click it in the pool
        if (Object.values(this.choices).includes(countryId)) {
            return;
        }

        // If it's already the pending one, deselect it
        if (this.pendingCountryId === countryId) {
            this.pendingCountryId = null;
        } else {
            // Select as pending
            this.pendingCountryId = countryId;
        }

        this.updateUI();
    }

    selectCountry(points, countryId) {
        // If this country is already selected in another slot, remove it from there
        for (const [p, id] of Object.entries(this.choices)) {
            if (id === countryId) {
                this.clearSlot(p);
            }
        }

        // Set the new choice
        this.choices[points] = countryId;
        this.updateUI();
    }

    removeCountry(event) {
        const points = event.currentTarget.dataset.points;
        this.clearSlot(points);
        this.updateUI();
    }

    clearSlot(points) {
        delete this.choices[points];
    }

    persistChoices() {
        if (!this.updateUrlValue) return;

        const formData = new FormData();
        for (const [points, countryId] of Object.entries(this.choices)) {
            formData.append(`choices[${points}]`, countryId);
        }

        fetch(this.updateUrlValue, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
    }

    updateUI() {
        // Save to session
        this.persistChoices();

        // Update all slots
        this.slotTargets.forEach((slot, index) => {
            const points = slot.dataset.points;
            const countryId = this.choices[points];
            const content = this.slotContentTargets[index];
            const input = this.inputTargets[index];
            const removeBtn = this.removeButtonTargets[index];

            const isLarge = slot.classList.contains('voting-slot-large');

            if (countryId) {
                const countryEl = this.countryTargets.find(c => c.dataset.countryId === countryId);
                if (countryEl) {
                    slot.classList.remove('slot-empty');
                    slot.classList.add('slot-filled', 'border-solid');

                    const flag = countryEl.querySelector('.display-6').textContent;
                    const name = countryEl.dataset.countryName;

                    content.innerHTML = `
                        <div class="d-flex align-items-center justify-content-center h-100 flex-column">
                            <span class="${isLarge ? 'display-1' : 'display-4'} mb-1">${flag}</span>
                            <span class="fw-semibold ${isLarge ? 'fs-5' : 'x-small'} text-truncate w-100 px-1">${name}</span>
                        </div>
                    `;
                    input.value = countryId;
                    removeBtn.classList.remove('d-none');
                }
            } else {
                slot.classList.add('slot-empty');
                slot.classList.remove('slot-filled', 'border-solid');
                content.innerHTML = `<span class="text-muted ${isLarge ? 'fs-6' : 'x-small'} italic text-center">${isLarge ? 'Tippe ein Land...' : '...'}</span>`;
                input.value = '';
                removeBtn.classList.add('d-none');
            }
        });

        // Update pool availability
        const selectedIds = Object.values(this.choices);
        this.countryTargets.forEach(country => {
            const countryId = country.dataset.countryId;
            if (selectedIds.includes(countryId)) {
                country.classList.add('opacity-50', 'selected-country');
                country.classList.remove('draggable', 'pending-selection');
                country.setAttribute('draggable', 'false');
            } else {
                country.classList.remove('opacity-50', 'selected-country');
                country.classList.add('draggable');
                country.setAttribute('draggable', 'true');

                if (this.pendingCountryId === countryId) {
                    country.classList.add('pending-selection');
                } else {
                    country.classList.remove('pending-selection');
                }
            }
        });

        // Update submit button
        this.submitButtonTarget.disabled = selectedIds.length < 10;
    }
}
