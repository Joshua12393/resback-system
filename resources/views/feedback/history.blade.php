@extends('layouts.guest')
@section('title', 'My Feedback History')

@section('content')
<div class="history-shell animate-fade-up">
    <div class="history-heading">
        <div>
            <span class="history-eyebrow">Private student record</span>
            <h1>My Feedback History</h1>
            <p>Review your submissions and their sentiment analysis results.</p>
        </div>
        <a href="{{ route('feedback.create') }}" class="btn btn-primary">Submit New Feedback</a>
    </div>

    <div class="history-privacy">
        <span aria-hidden="true">✓</span>
        <p><strong>Only you can access this page.</strong> Your identity is not displayed to faculty reviewers or included in feedback exports.</p>
    </div>

    @if($feedbacks->isEmpty())
        <div class="history-empty">
            <span class="history-empty-icon" aria-hidden="true">□</span>
            <h2>No feedback submitted yet</h2>
            <p>Your feedback and analysis results will appear here after your first submission.</p>
            <a href="{{ route('feedback.create') }}" class="btn btn-primary">Share Your Feedback</a>
        </div>
    @else
        <section class="card-dark history-dashboard-card">
            <div class="card-header">
                <h2>My feedbacks</h2>
                <span class="history-table-caption">Only CCIS · 10 per page</span>
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
                                <td class="history-date-cell">{{ $feedback->created_at->format('M d, Y h:i A') }}</td>
                                <td class="history-feedback-table-cell">{{ $feedback->content }}</td>
                                <td>{{ $feedback->category?->name ?? 'Uncategorized' }}</td>
                                <td>
                                    @if($feedback->status === 'rejected')
                                        <span class="badge badge-negative">Rejected</span>
                                    @elseif($analysis)
                                        <span class="badge badge-{{ $analysis->sentiment }}">{{ ucfirst($analysis->sentiment) }}</span>
                                        <span class="history-confidence">{{ number_format($analysis->confidence * 100, 0) }}% confidence</span>
                                    @else
                                        <span class="badge badge-pending">Pending</span>
                                    @endif
                                </td>
                                <td>{{ $feedback->status === 'rejected' ? 'Not analyzed' : ($analysis?->language_category ?? 'Unclassified') }}</td>
                                <td class="history-keywords-table-cell">
                                    <div class="keyword-list">
                                        @forelse($analysis?->keywords ?? [] as $keyword)
                                            <span class="keyword-chip">#{{ $keyword }}</span>
                                        @empty
                                            <span class="history-cell-muted">{{ $feedback->status === 'rejected' ? 'Not analyzed' : 'No keywords' }}</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>{{ ucfirst($feedback->status) }}</td>
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
