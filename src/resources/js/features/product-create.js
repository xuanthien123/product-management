import {
    formData,
    messageFor,
    requestApi,
    setButtonBusy,
    showMessage,
    tokenKey,
} from '../shared.js';
import { initializeProductCategories } from './product-categories.js';

export function initializeProductCreate() {
    const createForm = document.querySelector('[data-product-create-page]');

    if (!createForm) {
        return;
    }

    const message = document.querySelector('#create-product-message');
    const submitButton = createForm.querySelector('button[type="submit"]');
    initializeProductCategories(createForm);

    createForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        showMessage(message, '');
        setButtonBusy(submitButton, true, 'Creating…');

        try {
            await requestApi(createForm.action, {
                method: 'POST',
                data: formData(createForm),
            });
            window.location.assign('/dashboard');
        } catch (error) {
            showMessage(message, messageFor(error));
        } finally {
            setButtonBusy(submitButton, false);
        }
    });

    if (!sessionStorage.getItem(tokenKey)) {
        window.location.replace('/login');
    }
}
