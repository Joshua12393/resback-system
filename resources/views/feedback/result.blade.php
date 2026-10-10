@extends(auth()->check() ? 'layouts.app' : 'layouts.guest')
@section('title', $feedback->status === 'rejected' ? 'Feedback Rejected' : 'Feedback Submitted')

@section('content')
<div class="thankyou-card submission-result-card">
    @if($feedback->status === 'rejected')
        <div class="thankyou-icon thankyou-icon-rejected">!</div>
        <h1>Feedback Rejected</h1>
        <p>The submission matched the automatic rejection list and was not included in faculty analytics.</p>

        <div class="alert alert-error" style="margin-top:1.5rem;">
            Please submit genuine, respectful, and constructive feedback.
        </div>
    @else
        <div class="thankyou-icon">✓</div>
        <h1>Thank You!</h1>
        <p>Thanks for sharing your feedback. Your voice helps CCIS improve.</p>

    @endif

    <section class="result-summary submission-result-details" aria-label="Your submitted feedback and analysis">
        <h2 class="submission-result-label">Your feedback</h2>
        <p class="submission-result-feedback">{{ $feedback->content }}</p>

    @if($feedback->status !== 'rejected' && $feedback->sentimentResult)
        @php
            $sentiment = $feedback->sentimentResult->sentiment;
            $confidence = number_format($feedback->sentimentResult->confidence * 100, 0);
        @endphp

        <div class="submission-result-analysis">
            <div>
                <h2 class="submission-result-label">Sentiment</h2>
                <strong class="sentiment-{{ $sentiment }}">
                {{ $confidence }}% {{ ucfirst($sentiment) }}
                </strong>
                <span class="submission-result-confidence">Sentiment confidence</span>
            </div>
            <div>
                <h2 class="submission-result-label">Language</h2>
                <strong>{{ $feedback->sentimentResult->language_category ?? 'Unclassified' }}</strong>
                @if($feedback->sentimentResult->language_confidence !== null)
                    <span class="submission-result-confidence">{{ number_format($feedback->sentimentResult->language_confidence * 100, 0) }}% language confidence</span>
                @endif
            </div>
            <div class="submission-result-keywords">
                <h2 class="submission-result-label">Keywords</h2>
                <div class="keyword-list">
                    @forelse($feedback->sentimentResult->keywords ?? [] as $keyword)
                        <span class="keyword-chip">#{{ $keyword }}</span>
                    @empty
                        <span style="font-size:.8rem;color:var(--gray-500);">No keywords extracted.</span>
                    @endforelse
                </div>
            </div>
        </div>
    @elseif($feedback->status !== 'rejected')
        <div class="alert alert-error" style="margin-top:1.5rem;">
            Sentiment and language analysis could not be completed, but your feedback was still saved.
        </div>
    @endif
    </section>

    <div class="submission-result-actions">
    <a href="{{ route('feedback.create') }}" class="btn btn-primary">
        Submit Another Feedback
    </a>
        <a href="{{ route('feedback.history') }}" class="btn btn-ghost">View My Feedback</a>
    </div>
</div>
@endsection
