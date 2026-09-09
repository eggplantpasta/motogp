function clearRiderFormMessage() {
    const messageSection = document.getElementById('rider-form-message');

    if (messageSection) {
        messageSection.textContent = '';
        messageSection.className = 'motogp-form-message';
    }
}

function editRider(event) {
    document.getElementById('operation').value = 'update';

    const row = event.currentTarget.closest('tr');
    const riderId = row.getAttribute('data-rider-id');
    const riderNumber = row.getAttribute('data-rider-number');
    const riderActive = row.getAttribute('data-rider-active');
    const riderTeamId = row.getAttribute('data-team-id');
    const riderName = row.querySelector('.rider-name').textContent;

    clearRiderFormMessage();
    document.getElementById('invalid-rider_name').textContent = '';
    document.getElementById('invalid-race_number').textContent = '';

    document.getElementById('rider-id').value = riderId;
    document.getElementById('rider-number').value = riderNumber;
    document.getElementById('rider-name').value = riderName;
    document.getElementById('rider-team').value = riderTeamId || '';
    document.getElementById('rider-active').checked = riderActive === '1';

    toggleModal(event);
}

function addRider(event) {
    document.getElementById('operation').value = 'create';

    clearRiderFormMessage();
    document.getElementById('invalid-rider_name').textContent = '';
    document.getElementById('invalid-race_number').textContent = '';
    // Clear the form
    document.getElementById('rider-id').value = '';
    document.getElementById('rider-number').value = '';
    document.getElementById('rider-name').value = '';
    document.getElementById('rider-team').value = '';
    document.getElementById('rider-active').checked = true;
    toggleModal(event);
}

function deleteRider(event) {
    const row = event.currentTarget.closest('tr');

    const riderId = row.getAttribute('data-rider-id');
    const riderName = row.querySelector('.rider-name').textContent;

    confirmModal(
        `Delete "${riderName}"? This action cannot be undone.`,
        () => {
            document.getElementById('operation').value = 'delete';
            document.getElementById('rider-id').value = riderId;
            document.getElementById('rider-form').submit();
        }
    );
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-action="add-rider"]').forEach(button => {
        button.addEventListener('click', addRider);
    });

    document.querySelectorAll('[data-action="edit-rider"]').forEach(button => {
        button.addEventListener('click', editRider);
    });

    document.querySelectorAll('[data-action="delete-rider"]').forEach(button => {
        button.addEventListener('click', deleteRider);
    });
});
