
//
// JavaScript specific to the any page
//

function submitAfterModal(event) {
    toggleModal(event);  // Close the modal
    // Give the animation a moment, then submit the form
    setTimeout(() => {
        event.target.closest('form').submit();
    }, 100);
}

//
// JavaScript specific to the riders page
//

function editRider(event) {
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

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modal-edit');
    if (modal && modal.dataset.openOnLoad === 'true' && !modal.open) {
        openModal(modal);
    }
});
