// Generic function to enable a save button when input fields change
function enableSaveButtonOnInput(formId, saveButtonId) {
    const form = document.getElementById(formId);
    const saveButton = document.getElementById(saveButtonId);

    if (!form || !saveButton) {
        console.warn(`Form with ID "${formId}" or button with ID "${saveButtonId}" not found.`);
        return;
    }

    const inputs = form.querySelectorAll('input:not([type="hidden"])'); // Exclude hidden inputs

    inputs.forEach(input => {
        input.addEventListener('input', () => {
            saveButton.disabled = false; // Enable the save button when any input changes
        });
    });
}