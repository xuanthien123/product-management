import {
    emailKey,
    messageFor,
    requestApi,
    setButtonBusy,
    showMessage,
    tokenKey,
} from '../shared.js';

export function initializeChangePassword() {
    const page = document.querySelector('[data-change-password-page]');

    if (!page) {
        return;
    }

    const form = document.querySelector('#change-password-form');
    const message = document.querySelector('#change-password-message');
    const submitButton = form.querySelector('button[type="submit"]');
    const logoutButton = document.querySelector('#change-password-logout');

    if (!sessionStorage.getItem(tokenKey)) {
        window.location.replace('/login');
        return;
    }

    document.querySelector('#change-password-email').textContent =
        sessionStorage.getItem(emailKey) || '';

    logoutButton.addEventListener('click', async () => {
        logoutButton.disabled = true;

        try {
            await requestApi('/api/logout', { method: 'POST' });
            sessionStorage.removeItem(tokenKey);
            sessionStorage.removeItem(emailKey);
            window.location.assign('/login');
        } catch (error) {
            showMessage(message, messageFor(error));
            logoutButton.disabled = false;
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        showMessage(message, '');
        setButtonBusy(submitButton, true, 'Changing password...');

        const fields = new FormData(form);

        try {
            await requestApi('/api/password', {
                method: 'PUT',
                data: {
                    current_password: fields.get('current_password'),
                    password: fields.get('password'),
                    password_confirmation: fields.get('password_confirmation'),
                },
            });

            form.reset();
            showMessage(message, 'Password changed successfully.', 'success');
        } catch (error) {
            showMessage(message, messageFor(error));
        } finally {
            setButtonBusy(submitButton, false);
        }
    });
}
