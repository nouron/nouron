/**
 * "Wegschicken" — dismiss homeless colonists (GDD §6 "Überkapazität", A14).
 *
 * Alpine component inside the colonist chip popup of the resource bar
 * (resources/views/resources/resourcebar.blade.php). POSTs to
 * colony.colonists.dismiss and syncs the resource bar live (project
 * convention: every AJAX action that changes AP must update the bar without a
 * reload): AP chip (#resbar-ap) and colonist chip (.res-Sup) amounts, chip
 * state class, and the popup rows (dismissed / unfilled workplaces / staffing).
 *
 * Config via data attributes on the component root: data-url,
 * data-staffing-value (label template with ":pct"), data-failed.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('overcapDismiss', () => ({
        busy: false,
        done: false,
        error: '',
        dismissed: 0,
        departed: 0,
        staffing: '',

        async dismiss() {
            const config = this.$root.dataset;
            if (this.busy) return;
            this.busy = true;
            this.error = '';

            try {
                const response = await fetch(config.url, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                });
                const res = await response.json().catch(() => ({}));

                if (!response.ok || !res.ok) {
                    this.error = res.message ?? config.failed;

                    return;
                }

                this.dismissed = res.dismissed;
                this.departed = res.colonists.departed;
                this.staffing = config.staffingValue.replace(':pct', res.colonists.staffing_pct);
                this.done = true;
                this.syncResourceBar(res);
            } catch (e) {
                this.error = config.failed;
            } finally {
                this.busy = false;
            }
        },

        syncResourceBar(res) {
            const apChip = document.getElementById('resbar-ap');
            const apAmount = apChip?.querySelector('.res-amount');
            if (apAmount && res.apAvailable !== undefined) {
                apAmount.textContent = res.apAvailable;
                this.flash(apChip, 'ap-chip--flash');
            }

            const supChip = document.querySelector('.res-Sup');
            const supAmount = supChip?.querySelector('.res-amount');
            if (supAmount) {
                const fmt = (n) => Number(n).toLocaleString('de-DE');
                supAmount.textContent = `${fmt(res.colonists.present)} / ${fmt(res.colonists.cap)}`;
                supChip.classList.toggle('res-chip--over', res.colonists.homeless > 0);
                supChip.classList.toggle(
                    'res-chip--warning',
                    res.colonists.homeless === 0 && res.colonists.departed > 0,
                );
                this.flash(supChip, 'res-chip--flash');
            }
        },

        flash(chip, flashClass) {
            clearTimeout(chip._flashTimer);
            chip.classList.remove(flashClass);
            void chip.offsetWidth; // restart the animation
            chip.classList.add(flashClass);
            chip._flashTimer = setTimeout(() => chip.classList.remove(flashClass), 700);
        },
    }));
});
