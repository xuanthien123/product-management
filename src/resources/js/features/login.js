import {
    emailKey,
    messageFor,
    requestApi,
    setButtonBusy,
    showMessage,
    tokenKey,
} from '../shared.js';

export function initializeLogin() {
    const loginForm = document.querySelector('[data-login-form]');

    if (!loginForm) {
        return;
    }

    const message = document.querySelector('#login-message');
    const submitButton = loginForm.querySelector('button[type="submit"]');

    loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        showMessage(message, '');
        setButtonBusy(submitButton, true, 'Signing in…');

        try {
            const fields = new FormData(loginForm);
            const result = await requestApi(loginForm.action, {
                method: 'POST',
                requiresAuth: false,
                data: {
                    email: fields.get('email').trim(),
                    password: fields.get('password'),
                },
            });

            const data = result?.data;

            if (typeof data?.token !== 'string' || !data.token) {
                throw new Error('The login response did not include an access token.');
            }

            sessionStorage.setItem(tokenKey, data.token);
            sessionStorage.setItem(emailKey, data.user?.email || '');
            window.location.assign('/dashboard');
        } catch (error) {
            showMessage(message, messageFor(error));
        } finally {
            setButtonBusy(submitButton, false);
        }
    });
}
