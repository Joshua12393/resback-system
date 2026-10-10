@extends(auth()->check() ? 'layouts.app' : 'layouts.guest')
@section('title', 'Thank You!')

@section('content')
<div class="thankyou-card">
    <div class="thankyou-icon">✓</div>

    <h1>Thank You!</h1>
    <p>
        Your feedback has been received and stored confidentially.<br>
        Our AI system will analyze it to help improve the CCIS campus experience.
    </p>

    <div style="display:flex;flex-direction:column;gap:.75rem;align-items:center;">
        <a href="{{ route('feedback.create') }}" class="btn btn-primary">
            Submit Another Feedback
        </a>
        <span style="font-size:.8rem;color:var(--gray-400);">Your feedback makes a difference!</span>
    </div>

    {{-- Sentiment Analysis Result for Testing --}}
    @if(isset($feedback) && $feedback->sentimentResult)
    <div style="margin-top:2.5rem;text-align:left;background:var(--gray-50);border-radius:var(--radius-lg);padding:1.25rem 1.5rem;">
        <h3 style="font-size:.875rem;font-weight:600;color:var(--gray-700);margin-bottom:.875rem;">Test Result (Gemma 4 Analysis)</h3>
        <div style="display:flex;flex-direction:column;gap:.625rem;">
            <div style="display:flex;align-items:flex-start;gap:.75rem;font-size:.8125rem;color:var(--gray-600);">
                <span style="font-weight:600;width:80px;">Status:</span>
                <span>
                    @if($feedback->status == 'failed')
                        <span style="color:var(--danger)">Failed</span>
                    @else
                        <span style="color:var(--success)">Analyzed</span>
                    @endif
                </span>
            </div>
            <div style="display:flex;align-items:flex-start;gap:.75rem;font-size:.8125rem;color:var(--gray-600);">
                <span style="font-weight:600;width:80px;">Sentiment:</span>
                <span style="text-transform: capitalize;">{{ $feedback->sentimentResult->sentiment }} ({{ number_format($feedback->sentimentResult->confidence * 100, 1) }}%)</span>
            </div>
            <div style="display:flex;align-items:flex-start;gap:.75rem;font-size:.8125rem;color:var(--gray-600);">
                <span style="font-weight:600;width:80px;">Keywords:</span>
                <span>
                    @if(is_array($feedback->sentimentResult->keywords))
                        {{ implode(', ', $feedback->sentimentResult->keywords) }}
                    @else
                        None
                    @endif
                </span>
            </div>
            <div style="display:flex;align-items:flex-start;gap:.75rem;font-size:.8125rem;color:var(--gray-600);">
                <span style="font-weight:600;width:80px;">Feedback:</span>
                <span style="font-style: italic;">"{{ $feedback->content }}"</span>
            </div>
        </div>
    </div>
    @else
    {{-- What happens next --}}
    <div style="margin-top:2.5rem;text-align:left;background:var(--gray-50);border-radius:var(--radius-lg);padding:1.25rem 1.5rem;">
        <h3 style="font-size:.875rem;font-weight:600;color:var(--gray-700);margin-bottom:.875rem;">What happens next?</h3>
        <div style="display:flex;flex-direction:column;gap:.625rem;">
            <div style="display:flex;align-items:flex-start;gap:.75rem;font-size:.8125rem;color:var(--gray-600);">
                <span style="font-size:1rem;flex-shrink:0;margin-top:.05rem;">🧠</span>
                <span>Your feedback is processed by the Gemma 4 AI model for sentiment classification.</span>
            </div>
            <div style="display:flex;align-items:flex-start;gap:.75rem;font-size:.8125rem;color:var(--gray-600);">
                <span style="font-size:1rem;flex-shrink:0;margin-top:.05rem;">📊</span>
                <span>Results are aggregated and ranked by frequency to highlight top campus concerns.</span>
            </div>
            <div style="display:flex;align-items:flex-start;gap:.75rem;font-size:.8125rem;color:var(--gray-600);">
                <span style="font-size:1rem;flex-shrink:0;margin-top:.05rem;">🏫</span>
                <span>Administration and faculty review the insights to take action on urgent issues.</span>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
