function clearTeamFormMessage() {
    const messageSection = document.getElementById('team-form-message');

    if (messageSection) {
        messageSection.textContent = '';
        messageSection.className = 'motogp-form-message';
    }
}

function editTeam(event) {
    document.getElementById('operation').value = 'update';

    const row = event.currentTarget.closest('tr');

    document.getElementById('team-id').value =
        row.getAttribute('data-team-id');

    document.getElementById('team-name').value =
        row.getAttribute('data-team-name');

    document.getElementById('short-team-name').value =
        row.getAttribute('data-short-team-name');

    document.getElementById('manufacturer').value =
        row.getAttribute('data-manufacturer');

    clearTeamFormMessage();
    toggleModal(event);
}

function addTeam(event) {
    document.getElementById('operation').value = 'create';

    document.getElementById('team-id').value = '';
    document.getElementById('team-name').value = '';
    document.getElementById('short-team-name').value = '';
    document.getElementById('manufacturer').value = '';

    clearTeamFormMessage();
    toggleModal(event);
}

function deleteTeam(event) {
    const row = event.currentTarget.closest('tr');

    const teamId = row.getAttribute('data-team-id');
    const teamName = row.getAttribute('data-team-name');

    confirmModal(
        `Delete "${teamName}"? This action cannot be undone.`,
        () => {
            document.getElementById('operation').value = 'delete';
            document.getElementById('team-id').value = teamId;
            document.getElementById('team-form').submit();
        }
    );
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-action="add-team"]').forEach(button => {
        button.addEventListener('click', addTeam);
    });

    document.querySelectorAll('[data-action="edit-team"]').forEach(button => {
        button.addEventListener('click', editTeam);
    });

    document.querySelectorAll('[data-action="delete-team"]').forEach(button => {
        button.addEventListener('click', deleteTeam);
    });
});