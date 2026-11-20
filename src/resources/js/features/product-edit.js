import {
    formData,
    messageFor,
    requestApi,
    setButtonBusy,
    showMessage,
    tokenKey,
} from '../shared.js';
import { initializeProductCategories } from './product-categories.js';

export function initializeProductEdit() {
    const editForm = document.querySelector('[data-product-edit-page]');

    if (!editForm) {
        return;
    }

    const productId = editForm.dataset.productId;
    const fields = editForm.querySelector('fieldset');
    const loading = document.querySelector('#edit-product-loading');
    const message = document.querySelector('#edit-product-message');
    const submitButton = editForm.querySelector('button[type="submit"]');
    const categorySelector = initializeProductCategories(editForm);

    async function loadProduct() {
        try {
            const result = await requestApi(`/api/products/${encodeURIComponent(productId)}`);
            const product = result?.data;

            if (
                !product
                || typeof product.name !== 'string'
                || !Number.isFinite(Number(product.price))
                || Number(product.price) < 0
                || !Number.isInteger(Number(product.quantity))
                || Number(product.quantity) < 0
                || (product.description !== null
                    && product.description !== undefined
                    && typeof product.description !== 'string')
            ) {
                throw new Error('The server returned invalid product details.');
            }

            editForm.elements.name.value = product.name;
            editForm.elements.description.value = product.description || '';
            editForm.elements.price.value = product.price;
            editForm.elements.quantity.value = product.quantity;
            categorySelector.setSelectedCategories(
                Array.isArray(product.categories)
                    ? product.categories.map((category) => category.id)
                    : [],
            );
            fields.disabled = false;
            loading.hidden = true;
        } catch (error) {
            loading.hidden = true;
            showMessage(message, messageFor(error));
        }
    }

    editForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        setButtonBusy(submitButton, true, 'Saving…');
        showMessage(message, '');

        try {
            await requestApi(`/api/products/${encodeURIComponent(productId)}`, {
                method: 'PUT',
                data: formData(editForm),
            });
            showMessage(message, 'Product updated.', 'success');
        } catch (error) {
            showMessage(message, messageFor(error));
        } finally {
            setButtonBusy(submitButton, false);
        }
    });

    if (!sessionStorage.getItem(tokenKey)) {
        window.location.replace('/login');
    } else {
        loadProduct();
    }
}
