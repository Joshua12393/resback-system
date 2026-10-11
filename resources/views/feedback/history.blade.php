@extends(auth()->check() ? 'layouts.app' : 'layouts.guest')
@section('title', 'My Feedback History')
@section('page-title', 'Your Space')
@section('page-subtitle', 'Feedback you have shared')

@section('content')
<div class="history-shell animate-fade-up">
    <div class="history-heading">
        <div>
            <h1>My Feedback</h1>
            <p>Everything you've shared, in one place.</p>
        </div>
        <a href="{{ route('feedback.create') }}" class="btn btn-primary">Share Feedback</a>
    </div>

    <div class="history-privacy">
        <span aria-hidden="true">✓</span>
        <p>Your history is private. Reviewers won't see your identity.</p>
    </div>

    @if($feedbacks->isEmpty())
        <div class="history-empty">
            <div class="student-mascot"><x-brand-logo /></div>
            <h2>Your voice belongs here</h2>
            <p>Share your first feedback.</p>
            <a href="{{ route('feedback.create') }}" class="btn btn-primary">Share Feedback</a>
        </div>
    @else
        <section class="card-dark history-dashboard-card">
            <div class="card-header">
                <h2>Your submissions</h2>
                <span class="history-table-caption">CCIS · 10 per page</span>
            </div>

            <div class="table-wrapper history-dashboard-table-wrapper">
                <table class="data-table history-dashboard-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Feedback</th>
                            <th>Category</th>
                            <th>Sentiment</th>
                            <th>Language</th>
                            <th>Keywords</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($feedbacks as $feedback)
                            @php($analysis = $feedback->sentimentResult)
                            <tr>
                                <td class="history-date-cell" data-label="Date">{{ $feedback->created_at->format('M d, Y h:i A') }}</td>
                                <td class="history-feedback-table-cell" data-label="Feedback">
                                    @if(\Illuminate\Support\Str::length($feedback->content) > 140)
                                        <details class="feedback-details">
                                            <summary><span class="feedback-preview">{{ \Illuminate\Support\Str::limit($feedback->content, 140) }}</span><span class="feedback-expand-label">Read more</span></summary>
                                            <div class="feedback-full-text">{{ $feedback->content }}</div>
                                        </details>
                                    @else
                                        <span class="history-feedback-content">{{ $feedback->content }}</span>
                                    @endif
                                </td>
                                <td data-label="Category">{{ $feedback->category?->name ?? 'Uncategorized' }}</td>
                                <td data-label="Sentiment">
                                    @if($feedback->status === 'rejected')
                                        <span class="badge badge-negative">Rejected</span>
                                    @elseif($analysis)
                                        <span class="badge badge-{{ $analysis->sentiment }}">{{ ucfirst($analysis->sentiment) }}</span>
                                        <span class="history-confidence">{{ number_format($analysis->confidence * 100, 0) }}% confidence</span>
                                    @else
                                        <span class="badge badge-pending">Pending</span>
                                    @endif
                                </td>
                                <td data-label="Language">{{ $feedback->status === 'rejected' ? 'Not analyzed' : ($analysis?->language_category ?? 'Unclassified') }}</td>
                                <td class="history-keywords-table-cell" data-label="Keywords">
                                    <div class="keyword-list">
                                        @forelse($analysis?->keywords ?? [] as $keyword)
                                            <span class="keyword-chip">#{{ $keyword }}</span>
                                        @empty
                                            <span class="history-cell-muted">{{ $feedback->status === 'rejected' ? 'Not analyzed' : 'No keywords' }}</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td data-label="Status">{{ ucfirst($feedback->status) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if($feedbacks->hasPages())
        <div class="history-pagination">{{ $feedbacks->links() }}</div>
    @endif
</div>
@endsection
