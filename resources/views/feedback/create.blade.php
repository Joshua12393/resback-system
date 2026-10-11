@extends(auth()->check() ? 'layouts.app' : 'layouts.guest')
@section('title', 'Share Your Feedback')
@section('page-title', 'Share Feedback')
@section('page-subtitle', 'Your voice matters')

@section('content')
<div class="feedback-card animate-fade-up">

    {{-- Header --}}
    <div class="feedback-card-header">
        <div class="student-mascot"><x-brand-logo /></div>
        <h1>What's on your mind?</h1>
        <p>A concern, a suggestion, or something good?</p>
    </div>

    {{-- Body --}}
    <div class="feedback-card-body">

        {{-- Anonymous Badge --}}
        <div class="privacy-row">
            <span class="anonymous-badge">
                <span aria-hidden="true">✓</span> Reviewers won't see your identity
            </span>
        </div>

        {{-- Validation Errors --}}
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

        <form action="{{ route('feedback.store') }}" method="POST" id="feedbackForm" data-pending-label="Submitting feedback…">
            @csrf

            {{-- Fixed department scope --}}
            <div class="form-group student-department">
                <label for="category_display" class="form-label">Department</label>
                <input id="category_display" type="text" class="form-control" value="CCIS" readonly>
                <input type="hidden" name="category_id" value="{{ $defaultCategory->id }}">
                @error('category_id')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- Feedback Text --}}
            <div class="form-group">
                <label for="content" class="form-label">Your feedback</label>
                <textarea
                    id="content"
                    name="content"
                    class="form-control {{ $errors->has('content') ? 'is-invalid' : '' }}"
                    placeholder="Tell us what happened…"
                    maxlength="2000"
                    aria-describedby="feedbackHint charCounter{{ $errors->has('content') ? ' contentError' : '' }}"
                    @if($errors->has('content')) aria-invalid="true" @endif
                    rows="6"
                    oninput="updateCounter(this)"
                >{{ old('content') }}</textarea>
                <div class="form-hint" id="feedbackHint">Be specific. Keep it respectful.</div>
                <div class="char-counter" id="charCounter">0 / 2000</div>
                @error('content')
                    <div class="form-error" id="contentError">{{ $message }}</div>
                @enderror
            </div>

            {{-- Privacy Notice --}}
            <details class="privacy-notice student-privacy-details">
                <summary>How is my feedback used?</summary>
                <p>Saved to your account for your history. Reviewers and exports won't show your identity. AI analyzes the text to identify campus concerns.</p>
            </details>

            <div class="submission-warning" role="note">
                Feedback containing malicious words is automatically rejected.
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" id="submitBtn">
                Send Feedback
            </button>
        </form>

        <dialog class="feedback-confirmation" id="feedbackConfirmation" aria-labelledby="confirmationTitle">
            <form method="dialog">
                <h2 id="confirmationTitle">Ready to send?</h2>
                <p>Take a quick look before sending.</p>
                <div class="feedback-confirmation-actions">
                    <button type="submit" value="cancel" class="btn btn-secondary">Keep Editing</button>
                    <button type="button" class="btn btn-primary" id="confirmSubmitBtn">Send Feedback</button>
                </div>
            </form>
        </dialog>

    </div>
</div>
@endsection

@push('scripts')
<script>
    // Character counter
    function updateCounter(textarea) {
        const counter = document.getElementById('charCounter');
        const len = textarea.value.length;
        const max = 2000;
        counter.textContent = `${len} / ${max}`;
        counter.className = 'char-counter';
        if (len > 1800) counter.classList.add('near-limit');
        if (len >= max)  counter.classList.add('at-limit');
    }

    // Initialize counter on load
    const contentField = document.getElementById('content');
    if (contentField.value) updateCounter(contentField);

    const feedbackForm = document.getElementById('feedbackForm');
    const confirmationDialog = document.getElementById('feedbackConfirmation');
    const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');
    let submissionConfirmed = false;

    feedbackForm.addEventListener('submit', function (event) {
        if (!submissionConfirmed) {
            event.preventDefault();

            if (typeof confirmationDialog.showModal === 'function') {
                confirmationDialog.showModal();
            } else if (window.confirm('Confirm this feedback submission? If malicious words are detected, the system will reject it.')) {
                submissionConfirmed = true;
                feedbackForm.requestSubmit();
            }

            return;
        }

        const btn = document.getElementById('submitBtn');
        if (btn.disabled) { event.preventDefault(); return; }
        btn.disabled = true;
        btn.textContent = 'Sending…';
        btn.classList.add('is-submitting');
        btn.setAttribute('aria-busy', 'true');
    });

    confirmSubmitBtn.addEventListener('click', function () {
        submissionConfirmed = true;
        confirmationDialog.close();
        feedbackForm.requestSubmit();
    });

    window.addEventListener('pageshow', () => {
        submissionConfirmed = false;
        const btn = document.getElementById('submitBtn');
        btn.disabled = false;
        btn.textContent = 'Send Feedback';
        btn.classList.remove('is-submitting');
        btn.removeAttribute('aria-busy');
        if (confirmationDialog.open) confirmationDialog.close();
    });
</script>
@endpush
