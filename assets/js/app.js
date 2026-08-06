document.addEventListener('submit', function (event) {
    const deleteLink = event.target.querySelector('[name="action"][value="cancel"]');
    if (deleteLink && !confirm('Cancel this booking and process refund?')) {
        event.preventDefault();
    }
});
