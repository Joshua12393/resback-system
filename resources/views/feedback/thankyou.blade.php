@extends(auth()->check() ? 'layouts.app' : 'layouts.guest')
@section('title', 'Thank You!')
@section('page-title', 'Your Feedback')
@section('page-subtitle', '')

@section('content')
<div class="thankyou-card">
    <div class="thankyou-icon" aria-hidden="true">✓</div>
    <h1>Thanks for sharing!</h1>
    <p>Your feedback is saved.</p>
    @if(isset($feedback) && $feedback->sentimentResult)
        <details class="result-summary submission-result-details student-result-details">
            <summary>View feedback &amp; results</summary>
            <div class="student-result-content">
                <p class="submission-result-feedback">{{ $feedback->content }}</p>
                <div class="submission-result-analysis">
                    <div><h2 class="submission-result-label">Sentiment</h2><strong>{{ ucfirst($feedback->sentimentResult->sentiment) }}</strong></div>
                    <div><h2 class="submission-result-label">Language</h2><strong>{{ $feedback->sentimentResult->language_category ?? 'Unclassified' }}</strong></div>
                </div>
            </div>
        </details>
    @else
        <p class="student-next-note">Check My Feedback for your results.</p>
    @endif
    <div class="submission-result-actions mt-6">
        <a href="{{ route('feedback.create') }}" class="btn btn-primary">Share More</a>
        <a href="{{ route('feedback.history') }}" class="btn btn-secondary">My Feedback</a>
    </div>
</div>
@endsection
