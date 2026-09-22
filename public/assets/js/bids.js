function settlePayouts() {
    const form = document.getElementById('settle-payouts-form');

    confirmModal(
        'Settle these payouts? Player balances will be updated and this cannot be repeated.',
        () => {
            form.submit();
        },
    );
}

document.addEventListener('DOMContentLoaded', () => {
    document
        .querySelector('[data-action="settle-payouts"]')
        ?.addEventListener('click', settlePayouts);
});
