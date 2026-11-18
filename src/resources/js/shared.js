export const tokenKey = 'product-manager-token';
export const emailKey = 'product-manager-email';

export function showMessage(element, message, type = 'error') {
    element.textContent = message;
    element.classList.toggle('notice-error', type === 'error');
    element.classList.toggle('notice-success', type === 'success');
    element.hidden = message.length === 0;
}

export function messageFor(error) {
    const validationErrors = error.validationErrors;

    if (validationErrors) {
        return Object.values(validationErrors).flat().join(' ');
    }

    return error.message || 'Something went wrong. Please try again.';
}

export async function requestApi(url, { method = 'GET', data = null, requiresAuth = true } = {}) {
    const headers = { Accept: 'application/json' };

    if (requiresAuth) {
        const token = sessionStorage.getItem(tokenKey);

        if (!token) {
            window.location.replace('/login');
            throw new Error('Please sign in again.');
        }

        headers.Authorization = `Bearer ${token}`;
    }

    if (data !== null) {
        headers['Content-Type'] = 'application/json';
    }

    let response;

    try {
        response = await fetch(url, {
            method,
            headers,
            body: data === null ? undefined : JSON.stringify(data),
        });
    } catch {
        throw new Error('Could not connect to the server. Please try again.');
    }

    let result = null;

    if (response.status !== 204) {
        try {
            result = await response.json();
        } catch {
            throw new Error('The server returned an invalid response.');
        }
    }

    if (response.status === 401 && requiresAuth) {
        sessionStorage.removeItem(tokenKey);
        sessionStorage.removeItem(emailKey);
        window.location.replace('/login');
        throw new Error('Your session has expired. Please sign in again.');
    }

    if (!response.ok) {
        const error = new Error(result?.message || 'The request could not be completed.');
        error.validationErrors = result?.errors;
        throw error;
    }

    return result;
}

export function setButtonBusy(button, busy, busyText) {
    if (busy) {
        button.dataset.originalText = button.textContent;
        button.textContent = busyText;
        button.disabled = true;
        return;
    }

    button.textContent = button.dataset.originalText;
    button.disabled = false;
}

export function makeElement(tag, className, text) {
    const element = document.createElement(tag);

    if (className) {
        element.className = className;
    }

    if (text !== undefined) {
        element.textContent = text;
    }

    return element;
}

export function formData(form) {
    const fields = new FormData(form);

    return {
        name: fields.get('name').trim(),
        description: fields.get('description').trim() || null,
        price: Number(fields.get('price')),
        quantity: Number(fields.get('quantity')),
    };
}
