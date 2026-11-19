@extends('layouts.app')

@section('title', 'Edit profile | Product Manager')

@section('content')
<div class="dashboard-shell" data-profile-edit-page>
    <header class="topbar">
        <a class="login-brand" href="{{ route('dashboard') }}">
            <span class="brand-mark" aria-hidden="true">P</span>
            <span>Product Manager</span>
        </a>

        <div class="topbar-user">
            <span class="user-email" id="edit-profile-topbar-email"></span>
            <a class="button button-outline button-small" href="{{ route('change-password') }}">Change password</a>
            <button class="button button-outline button-small" id="edit-profile-logout" type="button">Sign out</button>
        </div>
    </header>

    <main class="dashboard-content profile-editor-content">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="{{ route('profile') }}">Profile</a>
            <span aria-hidden="true">/</span>
            <span>Edit profile</span>
        </nav>

        <section class="page-heading profile-editor-heading">
            <div>
                <p class="eyebrow">YOUR ACCOUNT</p>
                <h1>Edit your profile</h1>
                <p class="muted">Keep your personal details accurate and up to date.</p>
            </div>
            <a class="button button-outline" href="{{ route('profile') }}">Back to profile</a>
        </section>

        <div id="profile-edit-message" class="notice" role="status" hidden></div>

        <div class="profile-editor-layout">
            <aside class="panel profile-editor-aside">
                <p class="eyebrow">PROFILE PREVIEW</p>
                <div class="profile-editor-identity">
                    <span class="profile-avatar" id="edit-profile-avatar" aria-hidden="true">?</span>
                    <h2 class="profile-editor-name" id="edit-profile-preview-name">Loading profile...</h2>
                    <p class="profile-editor-email" id="edit-profile-preview-email"></p>
                </div>
                <div class="profile-editor-tip">
                    <span class="profile-editor-tip-icon" aria-hidden="true">i</span>
                    <p>Your profile details are only used to personalize your account.</p>
                </div>
            </aside>

            <section class="panel profile-edit-form-panel" aria-labelledby="profile-edit-heading">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">PERSONAL INFORMATION</p>
                        <h2 id="profile-edit-heading">Basic details</h2>
                    </div>
                </div>

                <p class="profile-edit-loading muted" id="profile-edit-loading" role="status">Loading your information...</p>

                <form class="profile-edit-form" id="profile-update-form" hidden>
                    <div class="profile-form-grid">
                        <div class="field profile-form-field-wide">
                            <label for="edit-profile-name">Full name</label>
                            <input
                                id="edit-profile-name"
                                name="name"
                                type="text"
                                maxlength="255"
                                autocomplete="name"
                                required
                            >
                        </div>
                        <div class="field profile-form-field-wide">
                            <label for="edit-profile-address">Address</label>
                            <input
                                id="edit-profile-address"
                                name="address"
                                type="text"
                                maxlength="255"
                                autocomplete="street-address"
                                placeholder="Your home or mailing address"
                            >
                        </div>
                        <div class="field">
                            <label for="edit-profile-date-of-birth">Date of birth</label>
                            <input
                                id="edit-profile-date-of-birth"
                                name="date_of_birth"
                                type="date"
                                max="{{ now()->toDateString() }}"
                            >
                        </div>
                        <div class="field">
                            <label for="edit-profile-sex">Sex</label>
                            <input
                                id="edit-profile-sex"
                                name="sex"
                                type="text"
                                maxlength="20"
                                placeholder="Optional"
                            >
                        </div>
                    </div>

                    <div class="profile-edit-form-footer">
                        <p class="muted">Your email address and access level cannot be changed here.</p>
                        <div class="profile-form-actions">
                            <a class="button button-outline" href="{{ route('profile') }}">Cancel</a>
                            <button class="button button-primary" type="submit">Save changes</button>
                        </div>
                    </div>
                </form>
            </section>
        </div>
    </main>
</div>
@endsection
