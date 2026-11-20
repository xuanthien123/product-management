@extends('layouts.app')

@section('title', 'Import products | Product Manager')

@section('content')
<div class="dashboard-shell">
    <header class="topbar">
        <a class="login-brand" href="{{ route('dashboard') }}">
            <span class="brand-mark" aria-hidden="true">P</span>
            <span>Product Manager</span>
        </a>

        <a class="button button-outline button-small import-back-link" href="{{ route('dashboard') }}">
            <span aria-hidden="true">←</span>
            <span> Back to products</span>
        </a>
    </header>

    <main class="dashboard-content create-content">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <span aria-hidden="true">/</span>
            <span>Import products</span>
        </nav>

        <section class="page-heading create-page-heading">
            <div>
                <p class="eyebrow">PRODUCT CATALOG</p>
                <h1>Import products</h1>
                <p class="muted">Upload a CSV, XLS, XLSX or macro-enabled XLSM file containing product data.</p>
            </div>
        </section>

        <section class="panel create-panel">
            <div class="panel-heading create-panel-heading">
                <div>
                    <p class="eyebrow">FILE UPLOAD</p>
                    <h2>Select spreadsheet</h2>
                </div>
                <a class="button button-outline button-small" href="{{ route('products.import-template') }}">
                    Download Excel template (.xlsm)
                </a>
            </div>

            <div id="import-product-message" class="notice" role="status" aria-live="polite" hidden></div>

            <form class="form-stack create-form" data-product-import-page>
                <div class="field">
                    <span class="field-label">Product file</span>
                    <input
                        id="import-file"
                        class="import-file-input"
                        name="file"
                        type="file"
                        accept=".xlsm,.xlsx,.xls,.csv"
                        aria-describedby="import-file-hint import-file-selection"
                        required
                    >
                    <label class="import-dropzone" for="import-file" data-import-dropzone>
                        <span class="import-dropzone-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M5 14.5v3A2.5 2.5 0 0 0 7.5 20h9a2.5 2.5 0 0 0 2.5-2.5v-3" />
                            </svg>
                        </span>
                        <span class="import-dropzone-copy">
                            <strong class="import-dropzone-prompt">Drop your file here or <span>browse</span></strong>
                            <strong class="import-selected-file" data-import-file-name aria-live="polite" hidden></strong>
                            <span id="import-file-selection" class="import-file-selection">Choose a spreadsheet to get started</span>
                        </span>
                        <span class="import-file-types">XLSM, XLSX, XLS or CSV <span>·</span> Max 10 MB</span>
                    </label>
                    <span class="field-hint" id="import-file-hint">
                            Required columns: name, price, quantity. Description and categories are optional.
                            In the XLSM template, select multiple categories from the dropdown; select a chosen category again to remove it.
                    </span>
                </div>

                <div class="create-actions">
                    <button class="button button-primary" type="submit">Import products</button>
                </div>
            </form>

            <section id="import-results" class="import-results" aria-labelledby="import-results-heading" hidden>
                <div class="import-results-header">
                    <div>
                        <p class="eyebrow">RESULTS</p>
                        <h2 id="import-results-heading">Import summary</h2>
                    </div>
                    <p id="import-results-summary" class="import-results-summary"></p>
                </div>

                <details id="import-failures-section" class="import-result-section import-result-failures" hidden>
                    <summary>
                        <span>Products not imported</span>
                        <span id="import-failures-count" class="count-badge"></span>
                    </summary>
                    <div id="import-failures-list" class="import-result-list"></div>
                </details>

                <details id="import-success-section" class="import-result-section import-result-success" hidden>
                    <summary>
                        <span>Products added successfully</span>
                        <span id="import-success-count" class="count-badge"></span>
                    </summary>
                    <div id="import-success-list" class="import-result-list"></div>
                </details>
            </section>
        </section>
    </main>
</div>
@endsection
