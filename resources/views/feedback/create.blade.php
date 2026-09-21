@extends('layouts.guest')
@section('title', 'Share Your Feedback')

@section('content')
<div class="feedback-card animate-fade-up">

    {{-- Header --}}
    <div class="feedback-card-header">
        <div class="feedback-step">Student feedback · CCIS</div>
        <h1>Share Your Feedback</h1>
        <p>Help us improve CCIS by sharing your campus experience. Every voice matters.</p>
    </div>

    {{-- Body --}}
    <div class="feedback-card-body">

        {{-- Anonymous Badge --}}
        <div class="privacy-row">
            <span class="anonymous-badge">
                <span aria-hidden="true">✓</span> Your identity stays private from reviewers
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

        <form action="{{ route('feedback.store') }}" method="POST" id="feedbackForm">
            @csrf

            {{-- Fixed department scope --}}
            <div class="form-group">
                <label for="category_display" class="form-label">Department</label>
                <input id="category_display" type="text" class="form-control" value="CCIS" readonly>
                <input type="hidden" name="category_id" value="{{ $defaultCategory->id }}">
                @error('category_id')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- Feedback Text --}}
            <div class="form-group">
                <label for="content" class="form-label">Your Feedback</label>
                <textarea
                    id="content"
                    name="content"
                    class="form-control {{ $errors->has('content') ? 'is-invalid' : '' }}"
                    placeholder="Describe your experience, concern, or suggestion in detail..."
                    maxlength="2000"
                    rows="6"
                    oninput="updateCounter(this)"
                >{{ old('content') }}</textarea>
                <div class="char-counter" id="charCounter">0 / 2000</div>
                @error('content')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- Privacy Notice --}}
            <div class="privacy-notice">
                <strong>Privacy Note:</strong>
                Your account is linked privately so you can access your feedback history. Your identity is not
                shown in the faculty dashboard or export. The content is analyzed using AI sentiment analysis
                to help identify campus concerns.
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" id="submitBtn">
                Submit Feedback
            </button>
        </form>

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

    // Prevent double-submit
    document.getElementById('feedbackForm').addEventListener('submit', function () {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.textContent = 'Submitting...';
    });
</script>
@endpush
