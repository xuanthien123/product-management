import {
    emailKey,
    messageFor,
    requestApi,
    setButtonBusy,
    showMessage,
    tokenKey,
} from '../shared.js';

export function initializeProfileEdit() {
    const page = document.querySelector('[data-profile-edit-page]');

    if (!page) {
        return;
    }

    const form = document.querySelector('#profile-update-form');
    const loading = document.querySelector('#profile-edit-loading');
    const message = document.querySelector('#profile-edit-message');
    const logoutButton = document.querySelector('#edit-profile-logout');
    const saveButton = form.querySelector('button[type="submit"]');

    if (!sessionStorage.getItem(tokenKey)) {
        window.location.replace('/login');
        return;
    }

    document.querySelector('#edit-profile-topbar-email').textContent =
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

    function renderUser(user) {
        document.querySelector('#edit-profile-topbar-email').textContent = user.email;
        document.querySelector('#edit-profile-avatar').textContent =
            user.name.trim().slice(0, 1).toUpperCase() || '?';
        document.querySelector('#edit-profile-preview-name').textContent = user.name;
        document.querySelector('#edit-profile-preview-email').textContent = user.email;

        form.elements.name.value = user.name || '';
        form.elements.address.value = user.address || '';
        form.elements.date_of_birth.value = user.date_of_birth
            ? String(user.date_of_birth).slice(0, 10)
            : '';
        form.elements.sex.value = user.sex || '';
        sessionStorage.setItem(emailKey, user.email);
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        showMessage(message, '');
        setButtonBusy(saveButton, true, 'Saving changes...');

        const fields = new FormData(form);

        try {
            const result = await requestApi('/api/update-profile', {
                method: 'PUT',
                data: {
                    name: fields.get('name').trim(),
                    address: fields.get('address').trim() || null,
                    date_of_birth: fields.get('date_of_birth') || null,
                    sex: fields.get('sex').trim() || null,
                },
            });

            if (
                typeof result?.data?.name !== 'string'
                || typeof result.data.email !== 'string'
            ) {
                throw new Error('The server returned an invalid profile response.');
            }

            renderUser(result.data);
            showMessage(message, 'Profile updated successfully.', 'success');
            sessionStorage.setItem(emailKey, result.data.email);
            window.location.assign('/profile');
        } catch (error) {
            showMessage(message, messageFor(error));
        } finally {
            setButtonBusy(saveButton, false);
        }
    });

    requestApi('/api/me')
        .then((result) => {
            const user = result?.data?.user;

            if (
                typeof user?.name !== 'string'
                || typeof user?.email !== 'string'
            ) {
                throw new Error('The server returned an invalid user profile.');
            }

            renderUser(user);
            loading.hidden = true;
            form.hidden = false;
        })
        .catch((error) => {
            loading.hidden = true;
            showMessage(message, messageFor(error));
        });
}
