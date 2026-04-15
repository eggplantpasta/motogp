
//
// JavaScript specific to the any page
//

function submitAfterModal(event) {
    toggleModal(event);  // Close the modal
    // Give the animation a moment, then submit the form
    setTimeout(() => {
        document.getElementById('rider-form').submit();
    }, 100);
}

function submitNoModal(event) {
    document.getElementById('rider-form').submit();
}

//
// JavaScript specific to the riders page
//

function editRider(event) {
    document.getElementById('operation').value = 'update';

    const messageSection = document.querySelector('.motogp-form-message');
    const row = event.target.closest('tr');
    const riderId = row.getAttribute('data-rider-id');
    const riderName = row.querySelector('.rider-name').textContent;
    const riderTeam = row.querySelector('.rider-team').textContent;
    const riderActive = row.querySelector('.rider-active').getAttribute('data-rider-active');

    // Clear messages
    if (messageSection) {
        messageSection.textContent = '';
        messageSection.className = 'motogp-form-message';
    }
    document.getElementById('invalid-rider_name').textContent = '';
    document.getElementById('invalid-rider-team').textContent = '';
    // Set the form
    document.getElementById('rider-id').value = riderId;
    document.getElementById('rider-name').value = riderName;
    document.getElementById('rider-team').value = riderTeam;
    document.getElementById('rider-active').checked = riderActive === '1';
    toggleModal(event);
}

function addRider(event) {
    document.getElementById('operation').value = 'create';

    const messageSection = document.querySelector('.motogp-form-message');
    // Clear messages
    if (messageSection) {
        messageSection.textContent = '';
        messageSection.className = 'motogp-form-message';
    }
    document.getElementById('invalid-rider_name').textContent = '';
    document.getElementById('invalid-rider-team').textContent = '';
    // Clear the form
    document.getElementById('rider-id').value = '';
    document.getElementById('rider-name').value = '';
    document.getElementById('rider-team').value = '';
    document.getElementById('rider-active').checked = true;
    toggleModal(event);
}

function deleteRider(event) {
    document.getElementById('operation').value = 'delete';

    if (confirm('Are you sure you want to delete this rider? This action cannot be undone.')) {
        const row = event.target.closest('tr');
        const riderId = row.getAttribute('data-rider-id');
        document.getElementById('rider-id').value = riderId;
        submitNoModal(event);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modal-edit');
    if (modal && modal.dataset.openOnLoad === 'true' && !modal.open) {
        openModal(modal);
    }
});
