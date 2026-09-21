@extends('layouts.guest')
@section('title', 'Feedback Submitted')

@section('content')
<div class="thankyou-card">
    @if($feedback->status === 'rejected')
        <div class="thankyou-icon thankyou-icon-rejected">!</div>
        <h1>Feedback Rejected</h1>
        <p>The submission matched the automatic rejection list and was not included in faculty analytics.</p>

        <div class="alert alert-error" style="margin-top:1.5rem;">
            Please submit genuine, respectful, and constructive feedback.
        </div>
    @else
        <div class="thankyou-icon">✓</div>
        <h1>Feedback Submitted</h1>
        <p>Thank you. Your feedback has been received.</p>

    @if($feedback->sentimentResult)
        @php
            $sentiment = $feedback->sentimentResult->sentiment;
            $confidence = number_format($feedback->sentimentResult->confidence * 100, 0);
        @endphp

        <div class="result-summary">
            <div style="font-size:2rem;font-weight:800;color:var(--gray-900);text-transform:capitalize;">
                {{ $confidence }}% {{ ucfirst($sentiment) }}
            </div>
            <div style="font-size:.8rem;color:var(--gray-500);margin-top:.25rem;">Sentiment confidence</div>

            <div style="margin-top:1.25rem;">
                <div style="font-size:.8rem;font-weight:600;color:var(--gray-600);margin-bottom:.65rem;">Keywords extracted</div>
                <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:.5rem;">
                    @forelse($feedback->sentimentResult->keywords ?? [] as $keyword)
                        <span class="badge badge-pending">#{{ $keyword }}</span>
                    @empty
                        <span style="font-size:.8rem;color:var(--gray-500);">No keywords extracted.</span>
                    @endforelse
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-error" style="margin-top:1.5rem;">
            Sentiment analysis could not be completed, but your feedback was still saved.
        </div>
    @endif
    @endif

    <a href="{{ route('feedback.create') }}" class="btn btn-primary">
        Submit Another Feedback
    </a>
</div>
@endsection
