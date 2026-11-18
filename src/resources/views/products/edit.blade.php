@extends('layouts.app')

@section('title', 'Edit product | Product Manager')

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
            <span>Edit product</span>
        </nav>

        <section class="page-heading create-page-heading">
            <div>
                <p class="eyebrow">PRODUCT CATALOG</p>
                <h1>Edit product</h1>
                <p class="muted">Update product details and inventory information.</p>
            </div>
        </section>

        <div class="create-layout">
            <section class="panel create-panel">
                <div class="panel-heading create-panel-heading">
                    <div>
                        <p class="eyebrow">PRODUCT DETAILS</p>
                        <h2>Basic information</h2>
                    </div>
                    <span class="panel-badge" aria-hidden="true">✎</span>
                </div>

                <div id="edit-product-loading" class="field-hint" role="status" aria-live="polite">
                    Loading product details…
                </div>
                <div id="edit-product-message" class="notice" role="status" aria-live="polite" hidden></div>

                <form
                    method="POST"
                    action="/api/products/{{ $product }}"
                    class="form-stack create-form product-edit-form"
                    data-product-edit-page
                    data-product-id="{{ $product }}"
                >
                    <fieldset class="product-edit-fields" disabled>
                        <legend class="visually-hidden">Product details</legend>

                        <div class="field">
                            <label for="edit-name">Product name <span class="required-mark" aria-hidden="true">*</span></label>
                            <input
                                id="edit-name"
                                name="name"
                                type="text"
                                placeholder="e.g. Studio Headphones"
                                autocomplete="off"
                                required
                            >
                            <span class="field-hint">Choose a clear name that your team will recognize.</span>
                        </div>

                        <div class="field">
                            <label for="edit-description">Description <span class="optional">(optional)</span></label>
                            <textarea
                                id="edit-description"
                                name="description"
                                rows="4"
                                placeholder="Add details about this product"
                            ></textarea>
                        </div>

                        <div class="create-form-divider"></div>

                        <div class="field-row">
                            <div class="field">
                                <label for="edit-price">Price <span class="required-mark" aria-hidden="true">*</span></label>
                                <input
                                    id="edit-price"
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
                                <label for="edit-quantity">Quantity <span class="required-mark" aria-hidden="true">*</span></label>
                                <input
                                    id="edit-quantity"
                                    name="quantity"
                                    type="number"
                                    min="0"
                                    step="1"
                                    inputmode="numeric"
                                    required
                                >
                                <span class="field-hint">Update the current stock on hand.</span>
                            </div>
                        </div>

                        <div class="create-form-footer">
                            <p class="required-note"><span aria-hidden="true">*</span> Required fields</p>
                            <div class="create-actions">
                                <a class="button button-outline" href="{{ route('dashboard') }}">Cancel</a>
                                <button class="button button-primary" type="submit">
                                    <span aria-hidden="true">✓</span>
                                    <span>Save changes</span>
                                </button>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </section>

            <aside class="create-aside">
                <section class="panel create-preview">
                    <p class="eyebrow">EDITING ITEM</p>
                    <div class="preview-icon" aria-hidden="true">▦</div>
                    <h2>Keep product details current</h2>
                    <p class="muted">Changes are saved to your inventory as soon as you update this product.</p>
                </section>

                <section class="create-tip">
                    <span class="tip-icon" aria-hidden="true">i</span>
                    <div>
                        <h3>Stock changes take effect immediately</h3>
                        <p>Set the quantity to the current number of units you have on hand.</p>
                    </div>
                </section>
            </aside>
        </div>
    </main>
</div>
@endsection
