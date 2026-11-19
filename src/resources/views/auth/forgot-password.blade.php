@extends('layouts.app')

@section('title', 'Forgot password | Product Manager')

@section('content')
<main class="login-page">
    <section class="login-card">
        <a class="login-brand" href="{{ route('login') }}">
            <span class="brand-mark" aria-hidden="true">P</span>
            <span>Product Manager</span>
        </a>

        <div class="login-copy">
            <p class="eyebrow">ACCOUNT RECOVERY</p>
            <h1>Forgot your password?</h1>
            <p class="muted">Enter your email and, if an account exists, we will send you a password reset link.</p>
        </div>

        <div id="forgot-password-message" class="notice" role="status" hidden></div>

        <form class="form-stack" id="forgot-password-form" data-forgot-password>
            <div class="field">
                <label for="recovery-email">Email address</label>
                <input
                    id="recovery-email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    placeholder="you@example.com"
                    required
                    autofocus
                >
            </div>
            <button class="button button-primary button-full" type="submit">Send reset link</button>
        </form>

        <p class="login-footnote"><a href="{{ route('login') }}">Back to sign in</a></p>
    </section>
</main>
@endsection
