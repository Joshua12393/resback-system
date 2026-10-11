@extends('layouts.app')
@php($isFacultyDashboard = auth()->user()->isFaculty())

@section('title', 'Feedback Dashboard')
@section('page-title', $isFacultyDashboard ? 'Feedback Overview' : 'Feedback Dashboard')
@section('page-subtitle', $isFacultyDashboard ? 'Listen. Understand. Improve.' : 'Sentiment and language analysis for submitted feedback')

@section('topbar-actions')
    <a href="{{ route('feedback.report', $reportQuery) }}" class="btn btn-primary btn-sm">
        {{ $isFacultyDashboard ? 'PDF Report' : 'Download PDF Report' }}
    </a>
    <a href="{{ route('feedback.export', $reportQuery) }}" class="btn btn-primary btn-sm">
        {{ $isFacultyDashboard ? 'Export Excel (.xlsx)' : 'Export Feedbacks (.xlsx)' }}
    </a>
@endsection

@section('content')
    <div class="stats-grid">
        <div class="stat-card stat-card-total">
            <div class="stat-icon indigo">#</div>
            <div><div class="stat-value">{{ number_format($totalFeedbacks) }}</div><div class="stat-label">{{ $isFacultyDashboard ? 'Feedback received' : 'Total feedbacks' }}</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">+</div>
            <div><div class="stat-value">{{ number_format($sentimentData['positive']) }}</div><div class="stat-label">Positive</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber">=</div>
            <div><div class="stat-value">{{ number_format($sentimentData['neutral']) }}</div><div class="stat-label">Neutral</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">−</div>
            <div><div class="stat-value">{{ number_format($sentimentData['negative']) }}</div><div class="stat-label">Negative</div></div>
        </div>
    </div>
    <section class="card-dark dashboard-filter-card" aria-labelledby="dashboard-filters-title">
        <div class="card-header dashboard-filter-heading">
            <div><h2 id="dashboard-filters-title">Explore feedback</h2><p class="chart-caption">{{ $isFacultyDashboard ? 'Choose filters, then apply.' : 'Choose a language and date range, then apply your filters.' }}</p></div>
            <a href="#ccisFeedbackPanel" class="dashboard-section-link">Jump to feedback ↓</a>
        </div>
        <div class="card-body">
            <form action="{{ route('dashboard') }}" method="GET" class="dashboard-filter-form">
                <div class="dashboard-language-field">
                    <label for="language_category" class="form-label">Language</label>
                    <select id="language_category" name="language_category" class="form-control" data-styled-select>
                        <option value="">All languages</option>
                        @foreach($filterLanguages as $language)
                            <option value="{{ $language }}" @selected($selectedLanguage === $language)>{{ $language }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="dashboard-date-field">
                    <label for="start_date" class="form-label">Start date</label>
                    <input
                        id="start_date"
                        name="start_date"
                        type="date"
                        data-styled-date
                        class="form-control"
                        value="{{ $dateRange->start?->format('Y-m-d') }}"
                        max="{{ now()->format('Y-m-d') }}"
                    >
                </div>
                <div class="dashboard-date-field">
                    <label for="end_date" class="form-label">End date</label>
                    <input
                        id="end_date"
                        name="end_date"
                        type="date"
                        data-styled-date
                        class="form-control"
                        value="{{ $dateRange->end?->format('Y-m-d') }}"
                        max="{{ now()->format('Y-m-d') }}"
                    >
                </div>
                <button type="submit" class="btn btn-primary btn-sm">{{ $isFacultyDashboard ? 'Apply' : 'Apply Filter' }}</button>
                @if($selectedLanguage || $dateRange->isActive())
                    <a href="{{ route('dashboard') }}" class="btn btn-ghost btn-sm">Clear Filters</a>
                @endif
            </form>
            @if($errors->has('start_date') || $errors->has('end_date'))
                <div class="dashboard-filter-error" role="alert">
                    {{ $errors->first('start_date') ?: $errors->first('end_date') }}
                </div>
            @endif
            <div class="dashboard-filter-summary">
                Currently showing: <strong>{{ $hasFilters ? $filterLabel : 'All categories and languages (mixed)' }}</strong>
            </div>
        </div>
    </section>

    <div class="dashboard-grid analytics-grid">
        <section class="card-dark">
            <div class="card-header">
                <h2>{{ $isFacultyDashboard ? 'Feedback sentiment' : 'Sentiment distribution' }}</h2>
                <span class="chart-caption">{{ $isFacultyDashboard ? 'Positive · Neutral · Negative' : 'Current dashboard filters' }}</span>
            </div>
            <div class="card-body chart-panel chart-panel-doughnut">
                <canvas id="sentimentDistributionChart" aria-label="Positive, neutral, and negative feedback chart"></canvas>
            </div>
        </section>

        <section class="card-dark">
            <div class="card-header">
                <h2>{{ $isFacultyDashboard ? 'Feedback over time' : 'Positive vs. negative trend' }}</h2>
                <span class="chart-caption">{{ $trendLabel }}</span>
            </div>
            <div class="card-body chart-panel">
                <canvas id="sentimentTrendChart" aria-label="Positive and negative feedback trend chart"></canvas>
            </div>
        </section>
    </div>

    <section class="card-dark" style="margin-bottom:1.5rem;">
        <div class="card-header">
            <div>
                <h2>{{ $isFacultyDashboard ? 'Concerns to review' : 'Most critical concerns' }}</h2>
                @if($isFacultyDashboard)
                    <details class="dashboard-ranking-help">
                        <summary>How are these ranked?</summary>
                        <p>Based on negative feedback, analysis confidence, and recent reports. A higher score means higher priority. Review the reports before deciding.</p>
                    </details>
                @else
                    <p class="chart-caption" style="margin-top:.25rem;">Ranked by negative volume, negative ratio, confidence, and recency</p>
                @endif
            </div>
            <span class="chart-caption">Top {{ $concernRankings->count() }}</span>
        </div>

        @if($concernRankings->isEmpty())
            <div class="empty-state"><h3>No ranked concerns yet</h3><p>{{ $isFacultyDashboard ? 'Check back after feedback is analyzed.' : 'Concern rankings appear after feedback has been analyzed.' }}</p></div>
        @else
            <div class="card-body">
                <div class="concern-chart-panel" style="height:{{ max(260, $concernRankings->count() * 46) }}px;">
                    <canvas id="concernRankingChart" aria-label="Critical concern ranking chart"></canvas>
                </div>
            </div>
            <div class="table-wrapper">
                <table class="data-table concern-table">
                    <thead>
                        <tr><th>Rank</th><th>Concern</th><th>Critical score</th><th>Negative</th><th>Total</th><th>Latest report</th><th>Evidence</th></tr>
                    </thead>
                    <tbody>
                    @foreach($concernRankings as $ranking)
                        <tr>
                            <td><strong style="color:var(--gray-900);">#{{ $loop->iteration }}</strong></td>
                            <td><strong style="color:var(--gray-900);">{{ $ranking['topic'] }}</strong></td>
                            <td>
                                <span class="critical-score critical-score-{{ $ranking['critical_score'] >= 75 ? 'high' : ($ranking['critical_score'] >= 45 ? 'medium' : 'low') }}">
                                    {{ number_format($ranking['critical_score'], 1) }}
                                </span>
                            </td>
                            <td>{{ number_format($ranking['negative_count']) }} ({{ number_format($ranking['negative_ratio'] * 100, 0) }}%)</td>
                            <td>{{ number_format($ranking['total_count']) }}</td>
                            <td style="white-space:nowrap;">{{ $ranking['latest_feedback_at']?->format('M d, Y') ?? '—' }}</td>
                            <td>
                                @if($ranking['low_evidence'])
                                    <span class="badge badge-neutral">Low evidence</span>
                                @endif
                                @if($ranking['examples'] !== [])
                                    <details class="concern-evidence">
                                        <summary>View reports</summary>
                                        <div class="concern-evidence-list">
                                            @foreach($ranking['examples'] as $example)
                                                <div>
                                                    <span>{{ $example['submitted_at']->format('M d') }}</span>
                                                    {{ $example['content'] }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @elseif(! $ranking['low_evidence'])
                                    <span style="color:var(--gray-500);">No negative examples</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <div class="dashboard-grid" style="grid-template-columns:1fr;">
        <section class="card-dark">
            <div class="card-header"><h2>{{ $isFacultyDashboard ? 'Feedback languages' : 'Language classification' }}</h2></div>
            <div class="card-body dashboard-language-list">
                @forelse($languageData as $language => $count)
                    <div class="dashboard-language-item">
                        <span style="color:var(--gray-700);">{{ $language }}</span>
                        <strong style="color:var(--gray-900);">{{ number_format($count) }}</strong>
                    </div>
                @empty
                    <div class="empty-state"><p>No language classifications yet.</p></div>
                @endforelse
            </div>
        </section>

    </div>

    @include('dashboard.partials.feedback-table')
    <p class="sr-only" role="status" id="feedbackLoadStatus"></p>
    <dialog id="feedbackReadDialog" class="feedback-confirmation feedback-reading-dialog" aria-labelledby="feedback-reading-title" aria-describedby="feedback-reading-content">
        <div class="feedback-reading-header">
            <h2 id="feedback-reading-title">Full feedback</h2>
            <button type="button" class="btn btn-secondary btn-sm" data-close-feedback-reader autofocus>Close</button>
        </div>
        <p class="feedback-reading-meta" data-feedback-reading-meta></p>
        <div id="feedback-reading-content" class="feedback-reading-content"></div>
    </dialog>
    @if(auth()->user()->isAdmin())
        <dialog id="feedbackDeleteConfirmation" class="feedback-confirmation" aria-labelledby="feedback-delete-title" aria-describedby="feedback-delete-description">
            <h2 id="feedback-delete-title">Delete this feedback?</h2>
            <p id="feedback-delete-description">This will permanently remove the feedback and its sentiment analysis. This cannot be undone.</p>
            <div class="feedback-confirmation-actions">
                <button type="button" class="btn btn-secondary" data-cancel-feedback-delete autofocus>Cancel</button>
                <button type="button" class="btn btn-danger" data-confirm-feedback-delete>Delete Feedback</button>
            </div>
        </dialog>
    @endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const chartTextColor = '#607082';
    const chartGridColor = 'rgba(96, 112, 130, 0.13)';
    const sentimentChartData = @json($sentimentChartData);
    const sentimentTrendData = @json($sentimentTrendData);
    const concernChartData = @json($concernChartData);

    new Chart(document.getElementById('sentimentDistributionChart'), {
        type: 'doughnut',
        data: {
            labels: sentimentChartData.labels,
            datasets: [{
                data: sentimentChartData.values,
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                borderWidth: 0,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: { legend: { position: 'bottom', labels: { color: chartTextColor, padding: 18 } } },
        },
    });

    new Chart(document.getElementById('sentimentTrendChart'), {
        type: 'line',
        data: {
            labels: sentimentTrendData.labels,
            datasets: [
                { label: 'Positive', data: sentimentTrendData.positive, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.15)', tension: .3, fill: true },
                { label: 'Negative', data: sentimentTrendData.negative, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,.12)', tension: .3, fill: true },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: { ticks: { color: chartTextColor, maxTicksLimit: 10 }, grid: { color: chartGridColor } },
                y: { beginAtZero: true, ticks: { color: chartTextColor, precision: 0 }, grid: { color: chartGridColor } },
            },
            plugins: { legend: { labels: { color: chartTextColor } } },
        },
    });

    const concernCanvas = document.getElementById('concernRankingChart');
    if (concernCanvas) {
        new Chart(concernCanvas, {
            type: 'bar',
            data: {
                labels: concernChartData.labels,
                datasets: [{
                    label: 'Critical score',
                    data: concernChartData.values,
                    backgroundColor: concernChartData.values.map(score => score >= 75 ? '#dc4c4c' : (score >= 45 ? '#d99b18' : '#2e6da4')),
                    borderRadius: 6,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { beginAtZero: true, max: 100, ticks: { color: chartTextColor }, grid: { color: chartGridColor } },
                    y: { ticks: { color: chartTextColor }, grid: { display: false } },
                },
                plugins: { legend: { display: false } },
            },
        });
    }
});
</script>
@endpush
