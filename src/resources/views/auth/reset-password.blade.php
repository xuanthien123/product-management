@extends('layouts.app')

@section('title', 'Reset password | Product Manager')

@section('content')
<main class="login-page">
    <section class="login-card">
        <a class="login-brand" href="{{ route('login') }}">
            <span class="brand-mark" aria-hidden="true">P</span>
            <span>Product Manager</span>
        </a>

        <div class="login-copy">
            <p class="eyebrow">ACCOUNT RECOVERY</p>
            <h1>Set a new password</h1>
            <p class="muted">Choose a new password for your account.</p>
        </div>

        <div id="reset-password-message" class="notice" role="status" hidden></div>

        <form class="form-stack" id="reset-password-form" data-reset-password>
            <input type="hidden" name="token" value="{{ request('token') }}">
            <div class="field">
                <label for="reset-new-password">New password</label>
                <input
                    id="reset-new-password"
                    name="password"
                    type="password"
                    autocomplete="new-password"
                    minlength="8"
                    required
                >
            </div>
            <div class="field">
                <label for="reset-password-confirmation">Confirm new password</label>
                <input
                    id="reset-password-confirmation"
                    name="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    minlength="8"
                    required
                >
            </div>
            <button class="button button-primary button-full" type="submit">Reset password</button>
        </form>

        <p class="login-footnote" id="reset-password-login-link" hidden>
            <a href="{{ route('login') }}">Back to sign in</a>
        </p>
    </section>
</main>
@endsection
