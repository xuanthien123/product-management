<dialog class="category-manager-dialog" data-category-manager-dialog aria-labelledby="category-manager-title">
    <div class="category-manager-header">
        <div>
            <p class="eyebrow">PRODUCT CATALOG</p>
            <h2 id="category-manager-title">Manage categories</h2>
        </div>
        <button class="category-manager-close" type="button" data-category-manager-close aria-label="Close category manager">
            ×
        </button>
    </div>

    <div class="notice" role="status" data-category-manager-message hidden></div>

    <div class="category-create-row">
        <div class="field">
            <label for="new-category-name">New category</label>
            <input id="new-category-name" type="text" maxlength="100" placeholder="e.g. Accessories">
        </div>
        <button class="button button-primary" type="button" data-category-create>Create</button>
    </div>

    <p class="field-hint category-manager-hint">Add, rename, or remove categories here. Removing one only unassigns it from products.</p>
    <div class="category-manager-list" data-category-manager-list aria-live="polite"></div>
</dialog>
