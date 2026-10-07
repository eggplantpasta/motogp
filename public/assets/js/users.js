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

function resetPassword(event) {
    const form = event.currentTarget.closest('.password-reset-form');
    const username = form.dataset.username;

    confirmModal(
        `Reset the password for "${username}"? The existing password will no longer work.`,
        () => {
            form.submit();
        },
        'reset',
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
    document
        .querySelectorAll('[data-action="reset-password"]')
        .forEach((button) => {
            button.addEventListener('click', resetPassword);
        });
});
