import {
    messageFor,
    requestApi,
    setButtonBusy,
    showMessage,
} from '../shared.js';

export function initializeForgotPassword() {
    const form = document.querySelector('[data-forgot-password]');

    if (!form) {
        return;
    }

    const message = document.querySelector('#forgot-password-message');
    const submitButton = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        showMessage(message, '');
        setButtonBusy(submitButton, true, 'Sending...');

        const fields = new FormData(form);

        try {
            const result = await requestApi('/api/forgot-password', {
                method: 'POST',
                requiresAuth: false,
                data: {
                    email: fields.get('email').trim(),
                },
            });

            showMessage(message, result?.message || 'If the account exists, a reset link will be sent.', 'success');
        } catch (error) {
            showMessage(message, messageFor(error));
        } finally {
            setButtonBusy(submitButton, false);
        }
    });
}

export function initializeResetPassword() {
    const form = document.querySelector('[data-reset-password]');

    if (!form) {
        return;
    }

    const message = document.querySelector('#reset-password-message');
    const submitButton = form.querySelector('button[type="submit"]');
    const loginLink = document.querySelector('#reset-password-login-link');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        showMessage(message, '');
        setButtonBusy(submitButton, true, 'Resetting...');

        const fields = new FormData(form);

        try {
            const result = await requestApi('/api/reset-password', {
                method: 'POST',
                requiresAuth: false,
                data: {
                    token: fields.get('token'),
                    password: fields.get('password'),
                    password_confirmation: fields.get('password_confirmation'),
                },
            });

            form.hidden = true;
            loginLink.hidden = false;
            showMessage(message, result?.message || 'Password reset successfully.', 'success');
        } catch (error) {
            showMessage(message, messageFor(error));
        } finally {
            setButtonBusy(submitButton, false);
        }
    });
}
