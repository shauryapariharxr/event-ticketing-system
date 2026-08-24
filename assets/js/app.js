document.addEventListener('DOMContentLoaded', function () {
    const seatCheckboxes = document.querySelectorAll('.seat-checkbox');
    const drawer = document.getElementById('checkout-drawer');
    const seatCountDisplay = document.getElementById('seat-count-display');
    const selectedSeatsDisplay = document.getElementById('selected-seats-display');
    const totalPriceDisplay = document.getElementById('total-price-display');

    if (seatCheckboxes.length > 0 && drawer) {
        function updateDrawer() {
            const selectedSeats = [];
            let total = 0;

            seatCheckboxes.forEach(cb => {
                if (cb.checked) {
                    selectedSeats.push({
                        label: cb.getAttribute('data-label'),
                        price: parseFloat(cb.getAttribute('data-price'))
                    });
                    total += parseFloat(cb.getAttribute('data-price'));
                }
            });

            const count = selectedSeats.length;
            if (count > 0) {
                seatCountDisplay.textContent = count;
                selectedSeatsDisplay.textContent = selectedSeats.map(s => s.label).join(', ');
                totalPriceDisplay.textContent = 'Rs. ' + total.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                drawer.classList.add('active');
            } else {
                drawer.classList.remove('active');
            }
        }

        seatCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateDrawer);
        });

        // Initialize on page load (in case browser restores checkbox state)
        updateDrawer();
    }
});

document.addEventListener('submit', function (event) {
    const deleteLink = event.target.querySelector('[name="action"][value="cancel"]');
    if (deleteLink && !confirm('Cancel this booking and process refund?')) {
        event.preventDefault();
    }
});
