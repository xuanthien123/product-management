import {
    emailKey,
    messageFor,
    requestApi,
    showMessage,
    tokenKey,
} from '../shared.js';

function formatDate(value, fallback) {
    if (!value) {
        return fallback;
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime())
        ? fallback
        : date.toLocaleDateString('vi-VN', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
        });
}

export function initializeProfile() {
    const profile = document.querySelector('[data-profile]');

    if (!profile) {
        return;
    }

    const message = document.querySelector('#profile-message');
    const loading = document.querySelector('#profile-loading');
    const content = document.querySelector('#profile-content');
    const logoutButton = document.querySelector('#profile-logout-button');

    if (!sessionStorage.getItem(tokenKey)) {
        window.location.replace('/login');
        return;
    }

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

    function renderProfile(user, isAdmin) {
        const roles = Array.isArray(user.roles)
            ? user.roles.map((role) => role?.name).filter((name) => typeof name === 'string' && name)
            : [];
        const accessLevel = roles.length
            ? roles.join(', ')
            : isAdmin
                ? 'Administrator'
                : 'Standard user';

        document.querySelector('#profile-topbar-email').textContent = user.email;
        document.querySelector('#profile-avatar').textContent = user.name.trim().slice(0, 1).toUpperCase() || '?';
        document.querySelector('#profile-name').textContent = user.name;
        document.querySelector('#profile-email').textContent = user.email;
        document.querySelector('#profile-name-detail').textContent = user.name;
        document.querySelector('#profile-email-detail').textContent = user.email;
        document.querySelector('#profile-address').textContent = user.address?.trim() || 'Not provided';
        document.querySelector('#profile-date-of-birth').textContent =
            formatDate(user.date_of_birth, 'Not provided');
        document.querySelector('#profile-sex').textContent = user.sex?.trim() || 'Not provided';
        document.querySelector('#profile-role').textContent = accessLevel;
        document.querySelector('#profile-created-at').textContent =
            formatDate(user.created_at, 'Not available');
        sessionStorage.setItem(emailKey, user.email);
    }

    requestApi('/api/me')
        .then((result) => {
            const currentUser = result?.data;
            const user = currentUser?.user;

            if (
                typeof user?.name !== 'string'
                || typeof user?.email !== 'string'
                || typeof currentUser?.is_admin !== 'boolean'
            ) {
                throw new Error('The server returned an invalid user profile.');
            }

            renderProfile(user, currentUser.is_admin);

            loading.hidden = true;
            content.hidden = false;
        })
        .catch((error) => {
            loading.hidden = true;
            showMessage(message, messageFor(error));
        });
}
