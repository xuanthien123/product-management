@extends('layouts.app')

@section('title', 'Sign in | Product Manager')

@section('content')
<main class="login-page">
    <section class="login-card">
        <div class="login-brand">
            <span class="brand-mark" aria-hidden="true">P</span>
            <span>Product Manager</span>
        </div>

        <div class="login-copy">
            <p class="eyebrow">WELCOME BACK</p>
            <h1>Sign in to your workspace</h1>
            <p class="muted">Manage your products and keep your inventory in view.</p>
        </div>

        <div id="login-message" class="notice notice-error" role="alert" hidden></div>
        <form method="POST" action="/api/login" class="form-stack" data-login-form>

            <div class="field">
                <label for="email">Email address</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    autocomplete="username"
                    placeholder="you@example.com"
                    required
                    autofocus
                >
                @error('email')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    required
                >
            </div>

            <button type="submit" class="button button-primary button-full">Sign in</button>
        </form>

        <p class="login-footnote">
            <a href="{{ route('password.request') }}">Forgot your password?</a>
        </p>
    </section>
</main>
@endsection
