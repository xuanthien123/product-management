@extends('layouts.app')

@section('title', 'Profile | Product Manager')

@section('content')
<div class="dashboard-shell" data-profile>
    <header class="topbar">
        <a class="login-brand" href="{{ route('dashboard') }}">
            <span class="brand-mark" aria-hidden="true">P</span>
            <span>Product Manager</span>
        </a>

        <div class="topbar-user">
            <span class="user-email" id="profile-topbar-email"></span>
            <a class="button button-outline button-small" href="{{ route('change-password') }}">Change password</a>
            <button class="button button-outline button-small" id="profile-logout-button" type="button">Sign out</button>
        </div>
    </header>

    <main class="dashboard-content">
        <section class="page-heading">
            <div>
                <p class="eyebrow">YOUR ACCOUNT</p>
                <h1>Personal profile</h1>
                <p class="muted">View your account details and access level.</p>
            </div>
            <a class="button button-primary" href="{{ route('profile.edit') }}">Edit profile</a>
        </section>

        <div id="profile-message" class="notice" role="alert" hidden></div>

        <section class="panel profile-panel" aria-label="Personal information">
            <p class="profile-loading muted" id="profile-loading" role="status">Loading your profile...</p>

            <div class="profile-content" id="profile-content" hidden>
                <div class="profile-heading">
                    <span class="profile-avatar" id="profile-avatar" aria-hidden="true"></span>
                    <div>
                        <h2 class="profile-name" id="profile-name"></h2>
                        <p class="profile-email" id="profile-email"></p>
                    </div>
                </div>

                <dl class="profile-details">
                    <div class="profile-detail">
                        <dt>Full name</dt>
                        <dd id="profile-name-detail"></dd>
                    </div>
                    <div class="profile-detail">
                        <dt>Email address</dt>
                        <dd id="profile-email-detail"></dd>
                    </div>
                    <div class="profile-detail">
                        <dt>Address</dt>
                        <dd id="profile-address"></dd>
                    </div>
                    <div class="profile-detail">
                        <dt>Date of birth</dt>
                        <dd id="profile-date-of-birth"></dd>
                    </div>
                    <div class="profile-detail">
                        <dt>Sex</dt>
                        <dd id="profile-sex"></dd>
                    </div>
                    <div class="profile-detail">
                        <dt>Access level</dt>
                        <dd id="profile-role"></dd>
                    </div>
                    <div class="profile-detail">
                        <dt>Member since</dt>
                        <dd id="profile-created-at"></dd>
                    </div>
                </dl>
            </div>
        </section>

    </main>
</div>
@endsection
