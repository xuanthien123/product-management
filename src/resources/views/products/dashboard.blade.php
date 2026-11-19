@extends('layouts.app')

@section('title', 'Dashboard | Product Manager')

@section('content')
<div class="dashboard-shell" data-dashboard>
    <header class="topbar">
        <a class="login-brand" href="{{ route('dashboard') }}">
            <span class="brand-mark" aria-hidden="true">P</span>
            <span>Product Manager</span>
        </a>

        <div class="topbar-user">
            <span class="user-email" id="dashboard-email"></span>
            <a class="button button-outline button-small" href="{{ route('profile') }}">Profile</a>
            <button class="button button-outline button-small" id="logout-button" type="button">Sign out</button>
        </div>
    </header>

    <main class="dashboard-content">
        <section class="page-heading">
            <div>
                <p class="eyebrow">OVERVIEW</p>
                <h1>Your products</h1>
                <p class="muted">Manage product details, prices, and stock from one place.</p>
            </div>
            <a class="button button-primary" href="{{ route('products.create') }}" data-admin-only hidden>Add product</a>
        </section>

        <div id="dashboard-message" class="notice" role="status" hidden></div>

        <section class="summary-grid" aria-label="Inventory summary">
            <article class="summary-card">
                <span class="summary-icon summary-icon-violet" aria-hidden="true">▦</span>
                <div>
                    <p class="summary-label">Total products</p>
                    <p class="summary-value" id="total-products">0</p>
                </div>
            </article>
            <article class="summary-card">
                <span class="summary-icon summary-icon-mint" aria-hidden="true">↗</span>
                <div>
                    <p class="summary-label">Units in stock</p>
                    <p class="summary-value" id="total-units">0</p>
                </div>
            </article>
            <article class="summary-card">
                <span class="summary-icon summary-icon-amber" aria-hidden="true">$</span>
                <div>
                    <p class="summary-label">Inventory value</p>
                    <p class="summary-value" id="inventory-value">0.00</p>
                </div>
            </article>
        </section>

        <div class="">
            <section class="panel inventory-panel">
                <div class="panel-heading inventory-heading">
                    <div>
                        <p class="eyebrow">INVENTORY</p>
                        <h2>Product list</h2>
                    </div>
                    <span class="count-badge" id="product-count">0 items</span>
                </div>

                <form id="product-filters" class="product-filters">
                    <div class="filter-field filter-search">
                        <label for="filter-search">Product name</label>
                        <input id="filter-search" name="search" type="search" placeholder="Search products">
                    </div>
                    <div class="filter-field">
                        <label for="filter-min-price">Min price</label>
                        <input id="filter-min-price" name="min_price" type="number" min="0" step="0.01" placeholder="Any">
                    </div>
                    <div class="filter-field">
                        <label for="filter-max-price">Max price</label>
                        <input id="filter-max-price" name="max_price" type="number" min="0" step="0.01" placeholder="Any">
                    </div>
                    <div class="filter-field">
                        <label for="filter-sort">Sort by price</label>
                        <select id="filter-sort" name="sort">
                            <option value="asc">Price: low to high</option>
                            <option value="desc">Price: high to low</option>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label for="filter-per-page">Per page</label>
                        <select id="filter-per-page" name="per_page">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="20">20</option>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button class="button button-primary button-small" type="submit">Apply</button>
                        <button class="button button-outline button-small" id="clear-product-filters" type="button">Clear</button>
                    </div>
                </form>

                <div class="product-list" id="product-list" aria-live="polite">
                    <p class="muted">Loading products…</p>
                </div>

                <nav class="pagination-bar" id="product-pagination" aria-label="Product list pages" hidden>
                    <button class="button button-outline button-small" id="previous-page" type="button">Previous</button>
                    <span class="pagination-status" id="pagination-status" aria-live="polite"></span>
                    <button class="button button-outline button-small" id="next-page" type="button">Next</button>
                </nav>
            </section>
        </div>
    </main>
</div>
@endsection
