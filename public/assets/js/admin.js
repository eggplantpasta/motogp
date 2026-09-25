function deleteStaleUsers(event) {
    const form = event.currentTarget.closest('.stale-user-delete-form');
    const count = form.dataset.count;
    const expiryDays = form.dataset.expiryDays;

    const message =
        `Delete ${count} stale registration(s)? ` +
        `This will permanently delete all unapproved user accounts ` +
        `older than ${expiryDays} days. This action cannot be undone.`;

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
        .querySelectorAll('[data-action="delete-stale-users"]')
        .forEach((button) => {
            button.addEventListener('click', deleteStaleUsers);
        });
});
