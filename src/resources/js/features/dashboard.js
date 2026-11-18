import {
    emailKey,
    makeElement,
    messageFor,
    requestApi,
    setButtonBusy,
    showMessage,
    tokenKey,
} from '../shared.js';

export function initializeDashboard() {
    const dashboard = document.querySelector('[data-dashboard]');

    if (!dashboard) {
        return;
    }

    const productList = document.querySelector('#product-list');
    const message = document.querySelector('#dashboard-message');
    const logoutButton = document.querySelector('#logout-button');
    const filterForm = document.querySelector('#product-filters');
    const clearFiltersButton = document.querySelector('#clear-product-filters');
    const pagination = document.querySelector('#product-pagination');
    const previousPageButton = document.querySelector('#previous-page');
    const nextPageButton = document.querySelector('#next-page');
    const paginationStatus = document.querySelector('#pagination-status');
    let currentPage = 1;
    let lastPage = 1;
    let latestRequest = 0;

    document.querySelector('#dashboard-email').textContent =
        sessionStorage.getItem(emailKey) || '';

    function renderEmptyState() {
        const emptyState = makeElement('div', 'empty-state');
        const hasFilters = Boolean(
            filterForm.elements.search.value.trim()
            || filterForm.elements.min_price.value
            || filterForm.elements.max_price.value,
        );
        emptyState.append(
            makeElement('span', 'empty-icon', '▦'),
            makeElement('h3', '', hasFilters ? 'No matching products' : 'No products yet'),
            makeElement(
                'p',
                'muted',
                hasFilters
                    ? 'Try adjusting your search or price filters.'
                    : 'Add your first product to start managing your inventory.',
            ),
        );
        productList.replaceChildren(emptyState);
    }

    function updateSummary(totalProducts, meta) {
        document.querySelector('#total-products').textContent = totalProducts.toLocaleString();
        document.querySelector('#total-units').textContent = Number(meta.total_units).toLocaleString();
        document.querySelector('#inventory-value').textContent = Number(meta.inventory_value).toLocaleString(
            undefined,
            { minimumFractionDigits: 2, maximumFractionDigits: 2 },
        );
        document.querySelector('#product-count').textContent =
            `${totalProducts.toLocaleString()} ${totalProducts === 1 ? 'item' : 'items'}`;
    }

    function makeProductCard(product) {
        const card = makeElement('article', 'product-card');
        const heading = makeElement('div', 'product-card-heading');
        const avatar = makeElement(
            'div',
            'product-avatar',
            String(product.name || '?').slice(0, 1).toUpperCase(),
        );
        avatar.setAttribute('aria-hidden', 'true');

        const title = makeElement('div', 'product-title');
        title.append(
            makeElement('h3', '', product.name),
            makeElement('p', '', product.description || 'No description'),
            makeElement('p', '', product.created_at ? new Date(product.created_at).toLocaleDateString('vi-VN', {
                timeZone: 'Asia/Ho_Chi_Minh',
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false,
            }) : 'Unknown date'),
        );

        const quantity = Number(product.quantity);
        const stockClass = quantity === 0 ? 'stock-empty' : quantity <= 5 ? 'stock-low' : '';
        const stock = makeElement(
            'span',
            `stock-badge ${stockClass}`.trim(),
            `${quantity.toLocaleString()} in stock`,
        );
        heading.append(avatar, title, stock);

        const footer = makeElement('div', 'product-card-footer');
        const price = makeElement('p', 'product-price');
        price.append(
            document.createTextNode(Number(product.price).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })),
            makeElement('span', '', ' per unit'),
        );

        const actions = makeElement('div', 'product-actions');
        const editButton = makeElement('button', 'button button-outline button-small', 'Edit');
        editButton.type = 'button';
        editButton.addEventListener('click', () => {
            window.location.assign(`/products/${product.id}/edit`);
        });

        const deleteButton = makeElement('button', 'button button-danger button-small', 'Delete');
        deleteButton.type = 'button';
        deleteButton.addEventListener('click', async () => {
            if (!window.confirm(`Delete "${product.name}"?`)) {
                return;
            }

            deleteButton.disabled = true;
            showMessage(message, '');

            try {
                await requestApi(`/api/products/${product.id}`, { method: 'DELETE' });
                showMessage(message, 'Product deleted.', 'success');
                await loadProducts();
            } catch (error) {
                showMessage(message, messageFor(error));
                deleteButton.disabled = false;
            }
        });

        actions.append(editButton, deleteButton);
        footer.append(price, actions);
        card.append(heading, footer);

        return card;
    }

    function renderProducts(products, meta) {
        updateSummary(meta.total, meta);

        if (products.length === 0) {
            renderEmptyState();
            return;
        }

        productList.replaceChildren(...products.map(makeProductCard));
    }

    function renderPagination(meta) {
        currentPage = meta.current_page;
        lastPage = meta.last_page;
        pagination.hidden = lastPage <= 1;
        paginationStatus.textContent = `Page ${currentPage} of ${lastPage}`;
        previousPageButton.disabled = currentPage <= 1;
        nextPageButton.disabled = currentPage >= lastPage;
    }

    async function loadProducts(page = currentPage) {
        const requestId = ++latestRequest;
        const formValues = new FormData(filterForm);
        const query = new URLSearchParams({
            page: String(page),
            per_page: formValues.get('per_page'),
            sort: formValues.get('sort'),
        });

        ['search', 'min_price', 'max_price'].forEach((key) => {
            const value = formValues.get(key).trim();

            if (value) {
                query.set(key, value);
            }
        });

        try {
            const result = await requestApi(`/api/products?${query.toString()}`);

            if (requestId !== latestRequest) {
                return;
            }

            const products = result?.data;
            const meta = result?.meta;

            if (
                !Array.isArray(products)
                || !Number.isInteger(meta?.current_page)
                || !Number.isInteger(meta?.last_page)
                || !Number.isInteger(meta?.total)
                || !Number.isFinite(Number(meta?.total_units))
                || !Number.isFinite(Number(meta?.inventory_value))
            ) {
                throw new Error('The server returned an invalid product list.');
            }

            if (meta.current_page > meta.last_page) {
                await loadProducts(meta.last_page);
                return;
            }

            renderProducts(products, meta);
            renderPagination(meta);
        } catch (error) {
            if (requestId !== latestRequest) {
                return;
            }

            showMessage(message, messageFor(error));
            productList.replaceChildren(makeElement('p', 'muted', 'Products could not be loaded.'));
            pagination.hidden = true;
        }
    }

    filterForm.addEventListener('submit', (event) => {
        event.preventDefault();

        const minPrice = filterForm.elements.min_price.value;
        const maxPrice = filterForm.elements.max_price.value;

        if (minPrice && maxPrice && Number(minPrice) > Number(maxPrice)) {
            showMessage(message, 'Minimum price cannot be greater than maximum price.');
            return;
        }

        showMessage(message, '');
        loadProducts(1);
    });

    clearFiltersButton.addEventListener('click', () => {
        filterForm.reset();
        showMessage(message, '');
        loadProducts(1);
    });

    previousPageButton.addEventListener('click', () => {
        if (currentPage > 1) {
            loadProducts(currentPage - 1);
        }
    });

    nextPageButton.addEventListener('click', () => {
        if (currentPage < lastPage) {
            loadProducts(currentPage + 1);
        }
    });

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

    if (!sessionStorage.getItem(tokenKey)) {
        window.location.replace('/login');
    } else {
        loadProducts();
    }
}
