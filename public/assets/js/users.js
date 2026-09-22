function deleteUser(event) {
    const form = event.currentTarget.closest('.user-delete-form');
    const username = form.dataset.username;
    const hasBids = form.dataset.hasBids === '1';

    const message = hasBids
        ? `Delete "${username}"? This will permanently delete the user and all of their bids. This action cannot be undone.`
        : `Delete "${username}"? This action cannot be undone.`;

    confirmModal(message, () => {
        form.submit();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document
        .querySelectorAll('[data-action="delete-user"]')
        .forEach((button) => {
            button.addEventListener('click', deleteUser);
        });
});
