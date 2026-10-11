<section class="card-dark feedback-pagination-panel" id="ccisFeedbackPanel" aria-live="polite">
    <div class="card-header">
        <h2>{{ auth()->user()->isFaculty() ? 'Student feedback' : ($hasFilters ? $filterLabel.' feedbacks' : 'Recent feedbacks') }}</h2>
        <span class="chart-caption">
            {{ auth()->user()->isFaculty() ? ($filterLabel ? 'Only '.$filterLabel : 'Latest submissions') : ($hasFilters ? 'Only '.$filterLabel.' · 10 per page' : 'Latest 10 mixed submissions') }}
        </span>
    </div>
    @if($recentFeedbacks->isEmpty())
        <div class="empty-state">
            <h3>{{ $hasOptionalFilters ? 'No feedback matches these filters' : 'No feedback yet' }}</h3>
            <p>{{ $hasOptionalFilters ? 'Try another language or a wider date range.' : 'Submitted feedback will appear here.' }}</p>
            @if($hasOptionalFilters)
                <a href="{{ route('dashboard') }}" class="btn btn-secondary mt-4">Clear Filters</a>
            @endif
        </div>
    @else
        <div class="table-wrapper">
            <table class="data-table dashboard-feedback-table">
                <thead>
                    <tr>
                        <th>Date</th><th>Feedback</th><th>Category</th><th>Sentiment</th><th>Language</th><th>Status</th>
                        @if(auth()->user()->isAdmin())
                            <th>Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                @foreach($recentFeedbacks as $feedback)
                    <tr>
                        <td data-label="Submitted" class="feedback-submitted"><time datetime="{{ $feedback->created_at->toIso8601String() }}">{{ $feedback->created_at->format('M d, Y') }}<span>{{ $feedback->created_at->format('h:i A') }}</span></time></td>
                        <td class="feedback-message-cell" data-label="Feedback">
                            @if(\Illuminate\Support\Str::length($feedback->content) > 140)
                                <details class="feedback-details" data-feedback-reader data-feedback-meta="{{ $feedback->created_at->format('M d, Y · h:i A') }} · {{ $feedback->category?->name ?? 'Uncategorized' }} · {{ $feedback->sentimentResult?->language_category ?? 'Unclassified' }}">
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
                        <td data-label="Category">{{ $feedback->category?->name ?? 'Uncategorized' }}</td>
                        <td data-label="Sentiment"><span class="badge badge-{{ $feedback->sentimentResult?->sentiment ?? 'pending' }}">{{ ucfirst($feedback->sentimentResult?->sentiment ?? 'pending') }}</span></td>
                        <td data-label="Language">{{ $feedback->sentimentResult?->language_category ?? 'Unclassified' }}</td>
                        <td data-label="Status" class="feedback-status">{{ ucfirst($feedback->status) }}</td>
                        @if(auth()->user()->isAdmin())
                            <td data-label="Actions">
                                <form action="{{ route('feedback.destroy', $feedback) }}" method="POST"
                                      data-feedback-delete data-pending-label="Deleting…">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        @endif
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
