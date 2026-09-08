
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
