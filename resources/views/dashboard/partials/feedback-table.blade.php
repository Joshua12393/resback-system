<section class="card-dark feedback-pagination-panel" id="ccisFeedbackPanel" aria-live="polite">
    <div class="card-header">
        <h2>{{ $hasFilters ? $filterLabel.' feedbacks' : 'Recent feedbacks' }}</h2>
        <span style="font-size:.75rem;color:var(--gray-400);">
            {{ $hasFilters ? 'Only '.$filterLabel.' · 10 per page' : 'Latest 10 mixed submissions' }}
        </span>
    </div>
    @if($recentFeedbacks->isEmpty())
        <div class="empty-state"><h3>No feedback yet</h3><p>Submitted feedback will appear here.</p></div>
    @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr><th>Date</th><th>Feedback</th><th>Category</th><th>Sentiment</th><th>Language</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($recentFeedbacks as $feedback)
                    <tr>
                        <td style="white-space:nowrap;">{{ $feedback->created_at->format('M d, Y h:i A') }}</td>
                        <td class="feedback-message-cell">
                            @if(\Illuminate\Support\Str::length($feedback->content) > 140)
                                <details class="feedback-details">
                                    <summary>
                                        <span class="feedback-preview">{{ \Illuminate\Support\Str::limit($feedback->content, 140) }}</span>
                                        <span class="feedback-expand-label">Read full feedback</span>
                                    </summary>
                                    <div class="feedback-full-text">{{ $feedback->content }}</div>
                                </details>
                            @else
                                <div class="feedback-full-text">{{ $feedback->content }}</div>
                            @endif
                        </td>
                        <td>{{ $feedback->category?->name ?? 'Uncategorized' }}</td>
                        <td><span class="badge badge-{{ $feedback->sentimentResult?->sentiment ?? 'pending' }}">{{ ucfirst($feedback->sentimentResult?->sentiment ?? 'pending') }}</span></td>
                        <td>{{ $feedback->sentimentResult?->language_category ?? 'Unclassified' }}</td>
                        <td>{{ ucfirst($feedback->status) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($hasFilters && $recentFeedbacks->hasPages())
            <div style="padding:1rem;">{{ $recentFeedbacks->links() }}</div>
        @endif
    @endif
</section>
