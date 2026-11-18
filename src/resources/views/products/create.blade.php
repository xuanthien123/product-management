@extends('layouts.app')

@section('title', 'Add product | Product Manager')

@section('content')
<div class="dashboard-shell">
    <header class="topbar">
        <a class="login-brand" href="{{ route('dashboard') }}">
            <span class="brand-mark" aria-hidden="true">P</span>
            <span>Product Manager</span>
        </a>

        <a class="button button-outline button-small" href="{{ route('dashboard') }}">
            <span aria-hidden="true">←</span>
            <span>Back to products</span>
        </a>
    </header>

    <main class="dashboard-content create-content">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <span aria-hidden="true">/</span>
            <span>New product</span>
        </nav>

        <section class="page-heading create-page-heading">
            <div>
                <p class="eyebrow">PRODUCT CATALOG</p>
                <h1>Add a product</h1>
                <p class="muted">Add a new item to your catalog and keep your inventory up to date.</p>
            </div>
        </section>

        <div class="create-layout">
            <section class="panel create-panel">
                <div class="panel-heading create-panel-heading">
                    <div>
                        <p class="eyebrow">PRODUCT DETAILS</p>
                        <h2>Basic information</h2>
                    </div>
                    <span class="panel-badge" aria-hidden="true">+</span>
                </div>

                <div id="create-product-message" class="notice" role="status" aria-live="polite" hidden></div>

                <form method="POST" action="/api/products" class="form-stack create-form" data-product-create-page>
                    <div class="field">
                        <label for="create-name">Product name <span class="required-mark" aria-hidden="true">*</span></label>
                        <input
                            id="create-name"
                            name="name"
                            type="text"
                            placeholder="e.g. Studio Headphones"
                            autocomplete="off"
                            required
                        >
                        <span class="field-hint">Choose a clear name that your team will recognize.</span>
                    </div>

                    <div class="field">
                        <label for="create-description">Description <span class="optional">(optional)</span></label>
                        <textarea
                            id="create-description"
                            name="description"
                            rows="4"
                            placeholder="Add details about this product"
                        ></textarea>
                    </div>

                    <div class="create-form-divider"></div>

                    <div class="field-row">
                        <div class="field">
                            <label for="create-price">Price <span class="required-mark" aria-hidden="true">*</span></label>
                            <input
                                id="create-price"
                                name="price"
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                                inputmode="decimal"
                                required
                            >
                            <span class="field-hint">Enter the price per unit.</span>
                        </div>

                        <div class="field">
                            <label for="create-quantity">Initial quantity <span class="required-mark" aria-hidden="true">*</span></label>
                            <input
                                id="create-quantity"
                                name="quantity"
                                type="number"
                                min="0"
                                step="1"
                                value="0"
                                inputmode="numeric"
                                required
                            >
                            <span class="field-hint">You can update stock at any time.</span>
                        </div>
                    </div>

                    <div class="create-form-footer">
                        <p class="required-note"><span aria-hidden="true">*</span> Required fields</p>
                        <div class="create-actions">
                            <a class="button button-outline" href="{{ route('dashboard') }}">Cancel</a>
                            <button class="button button-primary" type="submit">
                                <span aria-hidden="true">＋</span>
                                <span>Create product</span>
                            </button>
                        </div>
                    </div>
                </form>
            </section>

            <aside class="create-aside">
                <section class="panel create-preview">
                    <p class="eyebrow">READY TO ADD</p>
                    <div class="preview-icon" aria-hidden="true">▦</div>
                    <h2>One more for your catalog</h2>
                    <p class="muted">Your new product will be available in the inventory list as soon as it’s created.</p>
                </section>

                <section class="create-tip">
                    <span class="tip-icon" aria-hidden="true">i</span>
                    <div>
                        <h3>Keep your inventory accurate</h3>
                        <p>Set the current quantity now, or leave it at zero and update it later.</p>
                    </div>
                </section>
            </aside>
        </div>
    </main>
</div>
@endsection
