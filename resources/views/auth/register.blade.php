@extends('layouts.auth')
@section('title', 'Create Account')

@section('content')
<div class="auth-layout">
    <div class="auth-card">
        <span class="auth-card-eyebrow">Student registration</span>
        <h2 class="auth-title">Create an account</h2>
        <p class="auth-subtitle">Create a student account to submit and track your feedback session.</p>

        {{-- Errors --}}
        @if($errors->any())
            <div class="alert alert-error">
                <span>⚠️</span>
                <ul style="list-style:none;padding:0;margin:0;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST">
            @csrf

            <div class="registration-name-grid">
                <div class="form-group">
                    <label for="first_name" class="form-label">First Name</label>
                    <input
                        id="first_name"
                        type="text"
                        name="first_name"
                        value="{{ old('first_name') }}"
                        class="form-control {{ $errors->has('first_name') ? 'is-invalid' : '' }}"
                        placeholder="Juan"
                        required
                        autofocus
                        autocomplete="given-name"
                        data-name-field
                    >
                    @error('first_name') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label for="middle_name" class="form-label">Middle Name <span class="optional">(Optional)</span></label>
                    <input
                        id="middle_name"
                        type="text"
                        name="middle_name"
                        value="{{ old('middle_name') }}"
                        class="form-control {{ $errors->has('middle_name') ? 'is-invalid' : '' }}"
                        placeholder="Santos"
                        autocomplete="additional-name"
                        data-name-field
                    >
                    @error('middle_name') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group registration-last-name">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input
                        id="last_name"
                        type="text"
                        name="last_name"
                        value="{{ old('last_name') }}"
                        class="form-control {{ $errors->has('last_name') ? 'is-invalid' : '' }}"
                        placeholder="Dela Cruz"
                        required
                        autocomplete="family-name"
                        data-name-field
                    >
                    @error('last_name') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                    placeholder="you@example.com"
                    required
                    autocomplete="email"
                >
                @error('email') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="password-input-wrap">
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                        placeholder="Minimum 8 characters"
                        required
                        autocomplete="new-password"
                    >
                    <button type="button" class="password-toggle" data-password-toggle="password" data-password-label="password" aria-label="Show password" aria-pressed="false">
                        <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                            <circle cx="12" cy="12" r="2.5" />
                        </svg>
                        <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m3 3 18 18M10.6 6.2A9.9 9.9 0 0 1 12 6c6 0 9.5 6 9.5 6a15.5 15.5 0 0 1-2.2 2.9M6.6 6.7C4 8.4 2.5 12 2.5 12s3.5 6 9.5 6c1.4 0 2.7-.3 3.8-.8M9.9 9.9a3 3 0 0 0 4.2 4.2" />
                        </svg>
                    </button>
                </div>
                @error('password') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label">Confirm Password</label>
                <div class="password-input-wrap">
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        class="form-control"
                        placeholder="Repeat your password"
                        required
                        autocomplete="new-password"
                    >
                    <button type="button" class="password-toggle" data-password-toggle="password_confirmation" data-password-label="confirm password" aria-label="Show confirm password" aria-pressed="false">
                        <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                            <circle cx="12" cy="12" r="2.5" />
                        </svg>
                        <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m3 3 18 18M10.6 6.2A9.9 9.9 0 0 1 12 6c6 0 9.5 6 9.5 6a15.5 15.5 0 0 1-2.2 2.9M6.6 6.7C4 8.4 2.5 12 2.5 12s3.5 6 9.5 6c1.4 0 2.7-.3 3.8-.8M9.9 9.9a3 3 0 0 0 4.2 4.2" />
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:.5rem;">
                Create Account
            </button>
        </form>

        <div class="auth-footer-link">
            Already have an account?
            <a href="{{ route('login') }}">Sign in</a>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-name-field]').forEach((field) => {
        field.addEventListener('input', () => {
            field.value = field.value
                .replace(/[^\p{L}\p{M} ]/gu, '')
                .replace(/ {2,}/g, ' ')
                .replace(/^ /, '');
        });
    });

</script>
@endpush
