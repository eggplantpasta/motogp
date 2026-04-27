
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

function clearRiderFormMessage() {
    const messageSection = document.getElementById('rider-form-message');

    if (messageSection) {
        messageSection.textContent = '';
        messageSection.className = 'motogp-form-message';
    }
}

//
// JavaScript specific to the riders page
//

function editRider(event) {
    document.getElementById('operation').value = 'update';

    const row = event.target.closest('tr');
    const riderId = row.getAttribute('data-rider-id');
    const riderActive = row.getAttribute('data-rider-active');
    const riderTeamId = row.getAttribute('data-team-id');
    const riderName = row.querySelector('.rider-name').textContent;

    clearRiderFormMessage();
    document.getElementById('invalid-rider_name').textContent = '';
    // Set the form
    document.getElementById('rider-id').value = riderId;
    document.getElementById('rider-name').value = riderName;
    document.getElementById('rider-team').value = riderTeamId || '';
    document.getElementById('rider-active').checked = riderActive === '1';
    toggleModal(event);
}

function addRider(event) {
    document.getElementById('operation').value = 'create';

    clearRiderFormMessage();
    document.getElementById('invalid-rider_name').textContent = '';
    // Clear the form
    document.getElementById('rider-id').value = '';
    document.getElementById('rider-name').value = '';
    document.getElementById('rider-team').value = '';
    document.getElementById('rider-active').checked = true;
    toggleModal(event);
}

function deleteRider(event) {
    document.getElementById('operation').value = 'delete';

    const row = event.target.closest('tr');
    const riderId = row.getAttribute('data-rider-id');
    const riderName = row.querySelector('.rider-name').textContent;

    // Show confirmation modal
    const messageModal = document.getElementById('modal-confirm');
    const messageContent = document.getElementById('modal-confirm-content');
    const confirmBtn = document.getElementById('modal-confirm-btn');

    if (!messageModal || !messageContent || !confirmBtn) {
        return;
    }

    messageContent.textContent = `Are you sure you want to delete "${riderName}"? This action cannot be undone.`;

    // Clear any previous confirm handler
    confirmBtn.onclick = null;

    // Set up the confirm handler
    confirmBtn.onclick = (e) => {
        e.preventDefault();
        document.getElementById('rider-id').value = riderId;
        closeModal(messageModal);
        submitNoModal(event);
    };

    openModal(messageModal);
}

document.addEventListener('DOMContentLoaded', () => {
    // Check for modal-edit
    const editModal = document.getElementById('modal-edit');
    if (editModal && editModal.dataset.openOnLoad === 'true' && !editModal.open) {
        openModal(editModal);
    }

    // Check for feedback modal and open temporarily
    const feedbackModal = document.getElementById('modal-feedback');
    if (feedbackModal && feedbackModal.dataset.openOnLoad === 'true' && !feedbackModal.open) {
        openTimedModal(feedbackModal, 2000); // Auto-close after 2 seconds
    }
});
