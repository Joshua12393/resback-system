@extends('layouts.app')

@section('title', 'Profile Settings')
@section('page-title', 'Profile Settings')
@section('page-subtitle', 'Manage your nickname and profile photo')

@section('content')
<div class="profile-settings-shell">
    <section class="profile-settings-card">
        <div class="profile-settings-header">
            <div>
                <span class="profile-eyebrow">Account preferences</span>
                <h1>Profile Settings</h1>
                <p>Update the nickname and photo shown with your account.</p>
            </div>
        </div>


        @if($errors->any())
            <div class="alert alert-error">
                <ul class="profile-error-list">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('profile.photo.store') }}" method="POST" enctype="multipart/form-data" id="profilePhotoForm">
            @csrf

            <div class="profile-photo-section">
                <div class="profile-photo-preview" id="profilePhotoPreview">
                    @if(auth()->user()->profile_photo_url)
                        <img src="{{ auth()->user()->profile_photo_url }}" alt="{{ auth()->user()->display_name }}'s profile photo">
                    @else
                        <span>{{ strtoupper(substr(auth()->user()->display_name, 0, 1)) }}</span>
                    @endif
                </div>
                <div class="profile-photo-controls">
                    <div class="profile-photo-buttons">
                        <label for="profile_photo" class="btn btn-secondary btn-sm">Choose Photo</label>
                        <input id="profile_photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only">
                        <button type="button" class="btn btn-secondary btn-sm profile-remove-photo" data-remove-photo @if(! auth()->user()->profile_photo_path) hidden @endif>Remove Photo</button>
                    </div>
                    <p>JPG, PNG, or WebP. Maximum size: 2 MB.</p>
                    <p id="photoUploadStatus" role="status" aria-live="polite"></p>
                    <noscript><button type="submit" class="btn btn-primary btn-sm">Save Photo</button></noscript>
                </div>
            </div>
        </form>

        <form action="{{ route('profile.update') }}" method="POST" data-pending-label="Saving nickname…">
            @csrf
            @method('PATCH')
            <div class="form-group">
                <label for="nickname" class="form-label">Nickname</label>
                <div class="profile-nickname-row">
                    <input id="nickname" name="nickname" type="text" value="{{ old('nickname', auth()->user()->display_name) }}" class="form-control" minlength="3" maxlength="30" required data-nickname-field>
                    <button type="submit" class="btn btn-primary">Save Nickname</button>
                </div>
                <div class="form-hint">3–30 letters, numbers, or underscores. Administrators see this instead of your real name or email.</div>
            </div>
        </form>
    </section>
</div>
    <dialog id="removePhotoConfirmation" class="feedback-confirmation" aria-labelledby="remove-photo-title" aria-describedby="remove-photo-description">
        <h2 id="remove-photo-title">Remove your photo?</h2>
        <p id="remove-photo-description">Your profile will show your initials instead. You can upload another photo anytime.</p>
        <form action="{{ route('profile.photo.destroy') }}" method="POST" class="feedback-confirmation-actions" data-pending-label="Removing photo…">
            @csrf
            @method('DELETE')
            <button type="button" class="btn btn-secondary" data-cancel-remove-photo autofocus>Keep Photo</button>
            <button type="submit" class="btn btn-danger">Remove Photo</button>
        </form>
    </dialog>
@endsection

@push('scripts')
<script>
    const removePhotoDialog = document.getElementById('removePhotoConfirmation');
    document.querySelector('[data-remove-photo]')?.addEventListener('click', () => removePhotoDialog.showModal());
    document.querySelector('[data-cancel-remove-photo]')?.addEventListener('click', () => removePhotoDialog.close());

    document.querySelectorAll('[data-nickname-field]').forEach((field) => {
        field.addEventListener('input', () => {
            field.value = field.value.replace(/[^\p{L}\p{M}\p{N}_]/gu, '');
        });
    });

    document.getElementById('profile_photo')?.addEventListener('change', async (event) => {
        const file = event.target.files?.[0];
        if (!file) return;
        const input = event.target;
        const form = document.getElementById('profilePhotoForm');
        const data = new FormData(form);
        const label = document.querySelector('label[for="profile_photo"]');
        const remove = document.querySelector('[data-remove-photo]');
        const status = document.getElementById('photoUploadStatus');
        input.disabled = true;
        remove.disabled = true;
        label.textContent = 'Saving…';
        status.textContent = 'Saving your photo…';
        try {
            const response = await fetch(form.action, {
                method: 'POST', body: data, headers: { Accept: 'application/json' },
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.errors?.profile_photo?.[0] || result.message || 'Could not save your photo. Please try again.');
            const image = document.createElement('img');
            image.src = result.photo_url;
            image.alt = 'Your profile photo';
            document.getElementById('profilePhotoPreview').replaceChildren(image);
            document.querySelectorAll('.user-avatar').forEach(avatar => {
                const photo = image.cloneNode();
                photo.alt = '';
                avatar.replaceChildren(photo);
            });
            remove.hidden = false;
            status.textContent = '';
            const success = document.querySelector('[data-success-dialog]');
            document.getElementById('account-success-message').textContent = result.message;
            success.showModal();
            success.focus({ preventScroll: true });
        } catch (error) {
            status.textContent = error instanceof TypeError || error instanceof SyntaxError
                ? 'Could not save your photo. Check your connection and try again.' : error.message;
        } finally {
            input.disabled = false;
            input.value = '';
            remove.disabled = false;
            label.textContent = 'Choose Photo';
        }
    });
</script>
@endpush
