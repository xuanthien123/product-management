import {
    makeElement,
    messageFor,
    requestApi,
    setButtonBusy,
    showMessage,
} from '../shared.js';

export function initializeProductImport() {
    const form = document.querySelector('[data-product-import-page]');

    if (!form) {
        return;
    }

    const message = document.querySelector('#import-product-message');
    const results = document.querySelector('#import-results');
    const resultsSummary = document.querySelector('#import-results-summary');
    const failuresSection = document.querySelector('#import-failures-section');
    const failuresCount = document.querySelector('#import-failures-count');
    const failuresList = document.querySelector('#import-failures-list');
    const successSection = document.querySelector('#import-success-section');
    const successCount = document.querySelector('#import-success-count');
    const successList = document.querySelector('#import-success-list');
    const fileInput = form.elements.file;
    const dropzone = form.querySelector('[data-import-dropzone]');
    const fileName = form.querySelector('[data-import-file-name]');
    const fileSelection = document.querySelector('#import-file-selection');
    const submitButton = form.querySelector('button[type="submit"]');

    function formatFileSize(bytes) {
        if (bytes < 1024 * 1024) {
            return `${Math.max(1, Math.round(bytes / 1024))} KB`;
        }

        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    function displaySelectedFile(file) {
        if (!file) {
            fileName.hidden = true;
            dropzone.querySelector('.import-dropzone-prompt').hidden = false;
            fileSelection.textContent = 'Choose a spreadsheet to get started';
            dropzone.classList.remove('has-file');
            return;
        }

        fileName.textContent = file.name;
        fileName.hidden = false;
        dropzone.querySelector('.import-dropzone-prompt').hidden = true;
        fileSelection.textContent = formatFileSize(file.size);
        dropzone.classList.add('has-file');
    }

    fileInput.addEventListener('change', () => {
        displaySelectedFile(fileInput.files[0]);
    });

    ['dragenter', 'dragover'].forEach((eventName) => {
        dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.add('is-dragging');
        });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
        dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.remove('is-dragging');
        });
    });

    dropzone.addEventListener('drop', (event) => {
        const [file] = event.dataTransfer.files;

        if (!file) {
            return;
        }

        const transfer = new DataTransfer();
        transfer.items.add(file);
        fileInput.files = transfer.files;
        displaySelectedFile(file);
    });

    function displayValue(value) {
        return value === null || value === undefined || value === ''
            ? '—'
            : String(value);
    }

    function makeTable(products, includeFailures = false) {
        const table = makeElement('table', 'import-result-table');
        const headers = includeFailures
            ? ['Excel row', 'Name', 'Price', 'Quantity', 'Categories', 'Description', 'Reason']
            : ['Name', 'Price', 'Quantity', 'Categories', 'Description'];
        const head = document.createElement('thead');
        const headerRow = document.createElement('tr');

        headers.forEach((header) => {
            headerRow.append(makeElement('th', '', header));
        });
        head.append(headerRow);

        const body = document.createElement('tbody');
        products.forEach((entry) => {
            const product = includeFailures ? entry.product || {} : entry;
            const row = document.createElement('tr');
            const values = [
                ...(includeFailures ? [entry.row] : []),
                product.name,
                product.price,
                product.quantity,
                product.categories,
                product.description,
            ];

            values.forEach((value, index) => {
                const cellValue = index === (includeFailures ? 2 : 1)
                    && value !== null
                    && value !== ''
                    && Number.isFinite(Number(value))
                    ? Number(value).toFixed(2)
                    : displayValue(value);
                row.append(makeElement(
                    'td',
                    index === values.length - 1 ? 'import-result-description' : '',
                    cellValue,
                ));
            });

            if (includeFailures) {
                const reasonCell = document.createElement('td');
                const reasons = makeElement('ul', 'import-error-list');

                (Array.isArray(entry.errors) ? entry.errors : []).forEach((reason) => {
                    reasons.append(makeElement('li', '', reason));
                });
                reasonCell.append(reasons);
                row.append(reasonCell);
            }

            body.append(row);
        });

        table.append(head, body);
        const wrapper = makeElement('div', 'import-result-table-wrap');
        wrapper.append(table);

        return wrapper;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const file = fileInput.files[0];
        if (!file) {
            showMessage(message, 'Please select a product file.');
            return;
        }

        const data = new FormData();
        data.append('file', file);

        setButtonBusy(submitButton, true, 'Importing...');
        showMessage(message, '');
        results.hidden = true;

        try {
            const result = await requestApi('/api/products/import', {
                method: 'POST',
                data,
            });

            const meta = result?.meta;
            showMessage(
                message,
                result?.message || 'Import completed.',
                meta?.failed > 0 ? 'error' : 'success',
            );

            if (meta) {
                const failures = Array.isArray(meta.failures) ? meta.failures : [];
                const importedProducts = Array.isArray(meta.imported_products)
                    ? meta.imported_products
                    : [];
                const importedCount = Number(meta.imported) || 0;
                const failedCount = Number(meta.failed) || 0;

                resultsSummary.textContent =
                    `Added: ${importedCount} · Failed: ${failedCount}`;

                failuresSection.hidden = failures.length === 0;
                failuresSection.open = failures.length > 0;
                failuresCount.textContent = String(failedCount);
                failuresList.replaceChildren(makeTable(failures, true));

                successSection.hidden = importedProducts.length === 0;
                successSection.open = false;
                successCount.textContent = String(importedCount);
                successList.replaceChildren(makeTable(importedProducts));

                results.hidden = false;
            }

            form.reset();
            displaySelectedFile(null);
        } catch (error) {
            showMessage(message, messageFor(error));
        } finally {
            setButtonBusy(submitButton, false);
        }
    });
}
