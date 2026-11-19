@extends('layouts.app')

@section('title', 'Change password | Product Manager')

@section('content')
<div class="dashboard-shell" data-change-password-page>
    <header class="topbar">
        <a class="login-brand" href="{{ route('dashboard') }}">
            <span class="brand-mark" aria-hidden="true">P</span>
            <span>Product Manager</span>
        </a>

        <div class="topbar-user">
            <span class="user-email" id="change-password-email"></span>
            <a class="button button-outline button-small" href="{{ route('profile') }}">Profile</a>
            <button class="button button-outline button-small" id="change-password-logout" type="button">Sign out</button>
        </div>
    </header>

    <main class="dashboard-content">
        <section class="page-heading">
            <div>
                <p class="eyebrow">ACCOUNT SECURITY</p>
                <h1>Change password</h1>
                <p class="muted">Choose a new password to keep your account secure.</p>
            </div>
        </section>

        <section class="panel change-password-panel" aria-label="Change your password">
            <div id="change-password-message" class="notice" role="status" hidden></div>

            <form class="form-stack" id="change-password-form" data-change-password>
                <div class="field">
                    <label for="current-password">Current password</label>
                    <input
                        id="current-password"
                        name="current_password"
                        type="password"
                        autocomplete="current-password"
                        required
                    >
                </div>
                <div class="field">
                    <label for="new-password">New password</label>
                    <input
                        id="new-password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        minlength="8"
                        required
                    >
                </div>
                <div class="field">
                    <label for="new-password-confirmation">Confirm new password</label>
                    <input
                        id="new-password-confirmation"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        minlength="8"
                        required
                    >
                </div>
                <div>
                    <button class="button button-primary" type="submit">Change password</button>
                </div>
            </form>
        </section>
    </main>
</div>
@endsection
