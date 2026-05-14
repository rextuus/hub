import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['row', 'podium1', 'podium2', 'podium3', 'revealButton', 'voterStatus'];
    static values = {
        countries: Array,
        ballots: Array,
        sessionKey: String
    };

    connect() {
        this.results = this.countriesValue.map(c => ({
            ...c,
            totalPoints: 0,
            votes: []
        }));

        this.currentBallotIndex = parseInt(sessionStorage.getItem(this.sessionKey + '_index') || '0');
        this.currentStep = parseInt(sessionStorage.getItem(this.sessionKey + '_step') || '0'); // 0: load user, 1: 1-7 points, 2: 8,10,12 points

        // Restore state if we were already in progress
        for (let i = 0; i < this.currentBallotIndex; i++) {
            this.applyBallot(this.ballotsValue[i], true);
        }

        let ballotBeingRevealed = null;
        let revealedPointsSubset = [];

        // If we were in the middle of a ballot, we need to handle that.
        if (this.currentStep > 0) {
            ballotBeingRevealed = this.ballotsValue[this.currentBallotIndex];
            if (this.currentStep === 1) {
                revealedPointsSubset = [1, 2, 3, 4, 5, 6, 7];
                this.applyBallot(ballotBeingRevealed, true, revealedPointsSubset);
            } else if (this.currentStep === 2) {
                this.applyBallot(ballotBeingRevealed, true);
                this.currentBallotIndex++;
                this.currentStep = 0;
            }
        }

        this.sortResults();
        this.render(false, true, ballotBeingRevealed, revealedPointsSubset);
        this.updateButton();
    }

    nextStep() {
        if (this.currentBallotIndex >= this.ballotsValue.length) return;

        const ballot = this.ballotsValue[this.currentBallotIndex];

        if (this.currentStep === 0) {
            // "Load points of [User]" - actually we just move to state 1
            this.currentStep = 1;
            this.revealPoints(ballot, [1, 2, 3, 4, 5, 6, 7]);
        } else if (this.currentStep === 1) {
            // Load 8, 10, 12
            this.currentStep = 2;
            this.revealPoints(ballot, [8, 10, 12]);
        }

        if (this.currentStep === 2) {
            this.currentBallotIndex++;
            this.currentStep = 0;
        }

        sessionStorage.setItem(this.sessionKey + '_index', this.currentBallotIndex);
        sessionStorage.setItem(this.sessionKey + '_step', this.currentStep);

        this.updateButton();
    }

    reset() {
        if (!confirm('Möchtest du die Ergebnisse wirklich zurücksetzen?')) return;

        sessionStorage.removeItem(this.sessionKey + '_index');
        sessionStorage.removeItem(this.sessionKey + '_step');

        // Reset state
        this.results = this.countriesValue.map(c => ({
            ...c,
            totalPoints: 0,
            votes: []
        }));
        this.currentBallotIndex = 0;
        this.currentStep = 0;

        this.sortResults();
        this.render(false, true);
        this.updateButton();
    }

    revealPoints(ballot, pointsSubset) {
        this.applyBallot(ballot, false, pointsSubset);
        // Render points update first without sorting
        this.render(true, false, ballot, pointsSubset);

        // Find and animate the badges that were just added/updated
        setTimeout(() => {
            this.rowTargets.forEach(row => {
                const badges = row.querySelectorAll(`.voter-badge[data-voter-id="${ballot.id}"]`);
                badges.forEach(badge => {
                    const points = parseInt(badge.querySelector('.badge-back').innerText);
                    if (pointsSubset.includes(points)) {
                        badge.classList.remove('not-flipped');
                        badge.classList.add('is-flipped');
                    }
                });
            });
        }, 100);

        // Wait for points animation (2s) + some buffer, then sort and re-render
        setTimeout(() => {
            this.sortResults();
            this.render(true, true);
        }, 2500);
    }

    applyBallot(ballot, silent, pointsSubset = null) {
        ballot.votes.forEach(vote => {
            const pointsValue = parseInt(vote.points);
            if (pointsSubset && !pointsSubset.includes(pointsValue)) return;

            const country = this.results.find(c => c.id === parseInt(vote.countryId));
            if (country) {
                // Check if this specific vote was already applied to avoid duplicates on refresh/restore
                const voteExists = country.votes.find(v => v.ballotId === ballot.id && v.points === pointsValue);
                if (voteExists) return;

                country.totalPoints += pointsValue;
                country.votes.push({
                    ballotId: ballot.id,
                    voterInitial: ballot.voterInitial,
                    voterName: ballot.voterName,
                    points: pointsValue
                });
            }
        });
    }

    sortResults() {
        this.results.sort((a, b) => {
            if (b.totalPoints !== a.totalPoints) {
                return b.totalPoints - a.totalPoints;
            }
            return a.name.localeCompare(b.name);
        });
    }

    updateButton() {
        if (this.currentBallotIndex >= this.ballotsValue.length) {
            this.revealButtonTarget.disabled = true;
            this.revealButtonTarget.innerHTML = 'Alle Stimmen geladen';
            this.voterStatusTarget.innerHTML = 'Fertig!';
            return;
        }

        const nextBallot = this.ballotsValue[this.currentBallotIndex];
        if (this.currentStep === 0) {
            this.revealButtonTarget.innerHTML = `Punkte von <strong>${nextBallot.voterName}</strong> laden (1-7)`;
        } else if (this.currentStep === 1) {
            this.revealButtonTarget.innerHTML = `Die großen Punkte von <strong>${nextBallot.voterName}</strong> (8, 10, 12)`;
        }
        this.voterStatusTarget.innerHTML = `Nächster Wähler: <strong>${nextBallot.voterName}</strong>`;
    }

    render(animate = false, reorder = true, ballotBeingRevealed = null, revealedPointsSubset = []) {
        // Render Podium
        this.renderPodium(this.podium1Target, this.results[0], 1, animate);
        this.renderPodium(this.podium2Target, this.results[1], 2, animate);
        this.renderPodium(this.podium3Target, this.results[2], 3, animate);

        // Render Table
        const tbody = this.element.querySelector('tbody');

        // Map current DOM rows by country ID
        const rowMap = {};
        this.rowTargets.forEach(row => {
            rowMap[row.dataset.countryId] = row;
        });

        this.results.forEach((result, index) => {
            const row = rowMap[result.id];
            if (row) {
                // Update points
                const pointsEl = row.querySelector('.total-points');
                const oldPointsText = pointsEl.innerText || '0';
                const oldPoints = parseInt(oldPointsText);
                if (animate && oldPoints !== result.totalPoints) {
                    this.animateValue(pointsEl, oldPoints, result.totalPoints, 2000);
                    row.classList.add('bg-warning', 'bg-opacity-25');
                    setTimeout(() => row.classList.remove('bg-warning', 'bg-opacity-25'), 3000);
                } else if (!animate) {
                    pointsEl.innerText = result.totalPoints;
                }

                // Update rank
                const rankBadge = row.querySelector('.rank-badge');
                rankBadge.innerText = index + 1;
                rankBadge.className = `rank-badge rank-${index + 1}`;

                // Update highlight for top 3
                if (index < 3) {
                    row.classList.add('bg-opacity-10', 'bg-primary');
                } else {
                    row.classList.remove('bg-opacity-10', 'bg-primary');
                }

                // Update voter avatars
                const avatarsContainers = row.querySelectorAll('.voter-avatars-container');
                avatarsContainers.forEach(container => {
                    this.updateAvatars(container, result.votes, ballotBeingRevealed, revealedPointsSubset);
                });

                // Move row in DOM if needed (reorder)
                if (reorder && tbody.children[index] !== row) {
                    if (animate) {
                        row.style.transition = 'all 1.0s ease';
                        // row.classList.add('animate__animated', 'animate__pulse');
                    }
                    tbody.insertBefore(row, tbody.children[index]);
                }
            }
        });
    }

    renderPodium(target, result, rank, animate) {
        if (!result) {
            target.classList.add('d-none');
            return;
        }
        target.classList.remove('d-none');

        const nameEl = target.querySelector('.podium-name');
        const pointsEl = target.querySelector('.podium-points-value');
        const flagEl = target.querySelector('.fi');

        nameEl.innerText = result.name;
        flagEl.className = `fi fi-${result.countryCode.toLowerCase()}`;

        const oldPointsText = pointsEl.innerText || '0';
        const oldPoints = parseInt(oldPointsText);
        if (animate && oldPoints !== result.totalPoints) {
            this.animateValue(pointsEl, oldPoints, result.totalPoints, 2000);
            target.querySelector('.podium-card').classList.add('animate__animated', 'animate__bounce');
            setTimeout(() => target.querySelector('.podium-card').classList.remove('animate__animated', 'animate__bounce'), 2000);
        } else {
            pointsEl.innerText = result.totalPoints;
        }
    }

    updateAvatars(container, votes, ballotBeingRevealed = null, revealedPointsSubset = []) {
        if (!container) return;

        container.innerHTML = votes.map(v => {
            let isFlipped = true;
            if (ballotBeingRevealed && v.ballotId === ballotBeingRevealed.id) {
                if (!revealedPointsSubset.includes(v.points)) {
                    isFlipped = false;
                }
            }
            return `
                <div class="voter-badge ${isFlipped ? 'is-flipped' : 'not-flipped'}" title="${v.voterName}: ${v.points} Pkt" data-voter-id="${v.ballotId}">
                    <div class="badge-front">${v.voterInitial}</div>
                    <div class="badge-back">${v.points}</div>
                </div>
            `;
        }).join('');
    }

    animateValue(obj, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            obj.innerHTML = Math.floor(progress * (end - start) + start);
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }
}
