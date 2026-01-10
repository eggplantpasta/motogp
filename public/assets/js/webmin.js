// Webmin JavaScript

// Password visibility toggle
document.addEventListener('DOMContentLoaded', function() {
    const toggleButtons = document.querySelectorAll('.password-toggle');
    toggleButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const input = this.nextElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                input.style.setProperty('background-image', 'var(--webmin-icon-eye-open)');
            } else {
                input.type = 'password';
                input.style.setProperty('background-image', 'var(--webmin-icon-eye-closed)');
            }
        });
    });
});
