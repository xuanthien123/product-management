import {
    makeElement,
    messageFor,
    requestApi,
    setButtonBusy,
    showMessage,
} from '../shared.js';

export function initializeProductCategories(form) {
    const options = form.querySelector('[data-category-options]');
    const selectorMessage = form.querySelector('[data-category-selector-message]');
    const dialog = document.querySelector('[data-category-manager-dialog]');
    const managerMessage = dialog.querySelector('[data-category-manager-message]');
    const managerList = dialog.querySelector('[data-category-manager-list]');
    const createInput = dialog.querySelector('#new-category-name');
    const createButton = dialog.querySelector('[data-category-create]');
    const selectedIds = new Set();
    let categories = [];

    function renderOptions() {
        options.replaceChildren();

        if (categories.length === 0) {
            options.append(makeElement('span', 'field-hint', 'No categories yet. Add one using Manage categories.'));
            return;
        }

        categories.forEach((category) => {
            const label = makeElement('label', 'product-category-option');
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'category_ids[]';
            checkbox.value = String(category.id);
            checkbox.checked = selectedIds.has(category.id);
            checkbox.setAttribute('aria-label', category.name);
            if (checkbox.checked) {
                label.classList.add('is-selected');
            }
            label.append(checkbox, makeElement('span', '', category.name));
            options.append(label);
        });
    }

    function renderManagerList() {
        managerList.replaceChildren();

        if (categories.length === 0) {
            managerList.append(makeElement('p', 'category-manager-empty', 'No categories yet. Add your first one above.'));
            return;
        }

        categories.forEach((category) => {
            const row = makeElement('div', 'category-manager-row');
            const name = makeElement('span', 'category-manager-name', category.name);
            const actions = makeElement('div', 'category-manager-actions');
            const editButton = makeElement('button', 'button button-outline button-small', 'Rename');
            const deleteButton = makeElement('button', 'button button-danger button-small', 'Delete');

            editButton.type = 'button';
            deleteButton.type = 'button';
            editButton.addEventListener('click', () => editCategory(category, row));
            deleteButton.addEventListener('click', () => deleteCategory(category, deleteButton));

            actions.append(editButton, deleteButton);
            row.append(name, actions);
            managerList.append(row);
        });
    }

    async function loadCategories() {
        try {
            const result = await requestApi('/api/categories');
            const responseCategories = result?.data;

            if (
                !Array.isArray(responseCategories)
                || responseCategories.some((category) =>
                    !Number.isInteger(Number(category?.id)) || typeof category?.name !== 'string')
            ) {
                throw new Error('The server returned an invalid category list.');
            }

            categories = responseCategories.map((category) => ({
                id: Number(category.id),
                name: category.name,
            }));
            showMessage(selectorMessage, '');
            renderOptions();
            renderManagerList();
        } catch (error) {
            showMessage(selectorMessage, messageFor(error));
            managerList.replaceChildren();
        }
    }

    async function createCategory() {
        const name = createInput.value.trim();

        if (!name) {
            showMessage(managerMessage, 'Enter a category name.');
            createInput.focus();
            return;
        }

        showMessage(managerMessage, '');
        setButtonBusy(createButton, true, 'Creating…');

        try {
            const result = await requestApi('/api/categories', {
                method: 'POST',
                data: { name },
            });

            if (!Number.isInteger(Number(result?.data?.id))) {
                throw new Error('The server returned an invalid category.');
            }

            createInput.value = '';
            selectedIds.add(Number(result.data.id));
            await loadCategories();
            showMessage(managerMessage, 'Category created and selected.', 'success');
        } catch (error) {
            showMessage(managerMessage, messageFor(error));
        } finally {
            setButtonBusy(createButton, false);
        }
    }

    function editCategory(category, row) {
        row.classList.add('is-editing');
        row.replaceChildren();

        const input = document.createElement('input');
        input.className = 'category-manager-edit-input';
        input.type = 'text';
        input.maxLength = 100;
        input.value = category.name;
        input.setAttribute('aria-label', `Rename ${category.name}`);

        const actions = makeElement('div', 'category-manager-actions');
        const saveButton = makeElement('button', 'button button-primary button-small', 'Save');
        const cancelButton = makeElement('button', 'button button-outline button-small', 'Cancel');
        saveButton.type = 'button';
        cancelButton.type = 'button';

        saveButton.addEventListener('click', async () => {
            const name = input.value.trim();

            if (!name) {
                showMessage(managerMessage, 'Category name cannot be empty.');
                input.focus();
                return;
            }

            setButtonBusy(saveButton, true, 'Saving…');
            showMessage(managerMessage, '');

            try {
                await requestApi(`/api/categories/${category.id}`, {
                    method: 'PUT',
                    data: { name },
                });
                await loadCategories();
                showMessage(managerMessage, 'Category updated successfully.', 'success');
            } catch (error) {
                showMessage(managerMessage, messageFor(error));
                setButtonBusy(saveButton, false);
            }
        });
        cancelButton.addEventListener('click', renderManagerList);
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                saveButton.click();
            }
        });

        actions.append(saveButton, cancelButton);
        row.append(input, actions);
        input.focus();
        input.select();
    }

    async function deleteCategory(category, button) {
        if (!window.confirm(`Delete category "${category.name}"? It will be removed from assigned products.`)) {
            return;
        }

        setButtonBusy(button, true, 'Deleting…');
        showMessage(managerMessage, '');

        try {
            await requestApi(`/api/categories/${category.id}`, { method: 'DELETE' });
            selectedIds.delete(category.id);
            await loadCategories();
            showMessage(managerMessage, 'Category deleted.', 'success');
        } catch (error) {
            showMessage(managerMessage, messageFor(error));
            setButtonBusy(button, false);
        }
    }

    form.querySelector('[data-category-manager-open]').addEventListener('click', () => {
        showMessage(managerMessage, '');
        dialog.showModal();
    });

    dialog.querySelector('[data-category-manager-close]').addEventListener('click', () => {
        dialog.close();
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    createButton.addEventListener('click', createCategory);
    createInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            createCategory();
        }
    });

    options.addEventListener('change', (event) => {
        const checkbox = event.target;
        if (!(checkbox instanceof HTMLInputElement) || checkbox.type !== 'checkbox') {
            return;
        }

        const categoryId = Number(checkbox.value);
        if (checkbox.checked) {
            selectedIds.add(categoryId);
            checkbox.closest('.product-category-option').classList.add('is-selected');
        } else {
            selectedIds.delete(categoryId);
            checkbox.closest('.product-category-option').classList.remove('is-selected');
        }
    });

    loadCategories();

    return {
        setSelectedCategories(categoryIds) {
            selectedIds.clear();
            categoryIds.forEach((categoryId) => selectedIds.add(Number(categoryId)));
            renderOptions();
        },
    };
}
