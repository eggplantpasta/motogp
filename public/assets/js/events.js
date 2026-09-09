function clearEventFormMessage() {
    const messageSection = document.getElementById('event-form-message');

    if (messageSection) {
        messageSection.textContent = '';
        messageSection.className = 'motogp-form-message';
    }
}

function clearEventFormErrors() {
    document.getElementById('invalid-start_date').textContent = '';
    document.getElementById('invalid-name').textContent = '';
    document.getElementById('invalid-bids_open').textContent = '';
}

function editEvent(event) {
    const row = event.currentTarget.closest('tr');

    document.getElementById('operation').value = 'update';
    document.getElementById('event-form-heading').textContent = 'Edit Event';

    document.getElementById('event-id').value =
        row.getAttribute('data-event-id');

    document.getElementById('start-date').value =
        row.getAttribute('data-start-date');

    document.getElementById('event-name').value =
        row.getAttribute('data-event-name');

    document.getElementById('circuit').value =
        row.getAttribute('data-circuit');

    document.getElementById('country-code').value =
        row.getAttribute('data-country-code');

    document.getElementById('bids-open').checked =
        row.getAttribute('data-bids-open') === '1';

    clearEventFormMessage();
    clearEventFormErrors();

    toggleModal(event);
}

function addEvent(event) {
    document.getElementById('operation').value = 'create';
    document.getElementById('event-form-heading').textContent = 'Add Event';

    document.getElementById('event-id').value = '';
    document.getElementById('start-date').value = '';
    document.getElementById('event-name').value = '';
    document.getElementById('circuit').value = '';
    document.getElementById('country-code').value = '';
    document.getElementById('bids-open').checked = false;

    clearEventFormMessage();
    clearEventFormErrors();

    toggleModal(event);
}

function deleteEvent(event) {
    const row = event.currentTarget.closest('tr');

    const eventId = row.getAttribute('data-event-id');
    const eventName = row.getAttribute('data-event-name');

    confirmModal(
        `Delete "${eventName}"? This action cannot be undone.`,
        () => {
            document.getElementById('operation').value = 'delete';
            document.getElementById('event-id').value = eventId;
            document.getElementById('event-form').submit();
        }
    );
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-action="add-event"]').forEach(button => {
        button.addEventListener('click', addEvent);
    });

    document.querySelectorAll('[data-action="edit-event"]').forEach(button => {
        button.addEventListener('click', editEvent);
    });

    document.querySelectorAll('[data-action="delete-event"]').forEach(button => {
        button.addEventListener('click', deleteEvent);
    });
});