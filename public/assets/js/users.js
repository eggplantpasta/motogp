function deleteUser(event) {
    const form = event.currentTarget.closest('.user-delete-form');
    const username = form.dataset.username;
    const hasBids = form.dataset.hasBids === '1';

    const message = hasBids
        ? `Delete "${username}"? This user has bidding history. Deleting the user will permanently remove the account and all related bids. This action cannot be undone.`
        : `Delete "${username}"? This will permanently delete the user account. This action cannot be undone.`;

    confirmModal(
        message,
        () => {
            form.submit();
        },
        'delete',
    );
}

document.addEventListener('DOMContentLoaded', () => {
    document
        .querySelectorAll('[data-action="delete-user"]')
        .forEach((button) => {
            button.addEventListener('click', deleteUser);
        });
    document
        .querySelectorAll('[data-action="submit-form"]')
        .forEach((button) => {
            button.addEventListener('click', (event) => {
                event.currentTarget.closest('form').requestSubmit();
            });
        });
});
