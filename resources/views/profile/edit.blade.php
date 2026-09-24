@extends(auth()->user()->isAdmin() || auth()->user()->isFaculty() ? 'layouts.app' : 'layouts.guest')

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

        @if(session('success') && ! (auth()->user()->isAdmin() || auth()->user()->isFaculty()))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-error">
                <ul class="profile-error-list">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            <div class="profile-photo-section">
                <div class="profile-photo-preview" id="profilePhotoPreview">
                    @if(auth()->user()->profile_photo_url)
                        <img src="{{ auth()->user()->profile_photo_url }}" alt="{{ auth()->user()->display_name }}'s profile photo">
                    @else
                        <span>{{ strtoupper(substr(auth()->user()->display_name, 0, 1)) }}</span>
                    @endif
                </div>
                <div class="profile-photo-controls">
                    <label for="profile_photo" class="btn btn-secondary btn-sm">Choose Photo</label>
                    <input id="profile_photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" class="profile-photo-input">
                    <p>JPG, PNG, or WebP. Maximum size: 2 MB.</p>
                    @if(auth()->user()->profile_photo_path)
                        <label class="profile-remove-option">
                            <input type="checkbox" name="remove_profile_photo" value="1">
                            Remove current photo
                        </label>
                    @endif
                </div>
            </div>

            <div class="form-group">
                <label for="nickname" class="form-label">Nickname</label>
                <input id="nickname" name="nickname" type="text" value="{{ old('nickname', auth()->user()->display_name) }}" class="form-control" minlength="3" maxlength="30" required data-nickname-field>
                <div class="form-hint">3–30 letters, numbers, or underscores. Administrators see this instead of your real name or email.</div>
            </div>

            <div class="profile-actions">
                <button type="submit" class="btn btn-primary">Save Profile</button>
            </div>
        </form>
    </section>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-nickname-field]').forEach((field) => {
        field.addEventListener('input', () => {
            field.value = field.value.replace(/[^\p{L}\p{M}\p{N}_]/gu, '');
        });
    });

    document.getElementById('profile_photo')?.addEventListener('change', (event) => {
        const file = event.target.files?.[0];
        if (!file || !file.type.startsWith('image/')) return;

        const preview = document.getElementById('profilePhotoPreview');
        const image = document.createElement('img');
        image.src = URL.createObjectURL(file);
        image.alt = 'Selected profile photo preview';
        image.onload = () => URL.revokeObjectURL(image.src);
        preview.replaceChildren(image);
    });
</script>
@endpush
