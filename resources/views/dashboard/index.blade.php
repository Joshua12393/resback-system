@extends('layouts.app')

@section('title', 'Feedback Dashboard')
@section('page-title', 'Feedback Dashboard')
@section('page-subtitle', 'Sentiment and language analysis for submitted feedback')

@section('topbar-actions')
    <a href="{{ route('feedback.report', $reportQuery) }}" class="btn btn-ghost btn-sm">
        Download PDF Report
    </a>
    <a href="{{ route('feedback.export', $reportQuery) }}" class="btn btn-primary btn-sm">
        Export Feedbacks (.xlsx)
    </a>
@endsection

@section('content')
    <section class="card-dark" style="margin-bottom:1.5rem;">
        <div class="card-body">
            <form action="{{ route('dashboard') }}" method="GET" class="dashboard-filter-form">
                <div style="flex:1;min-width:240px;">
                    <label for="language_category" class="form-label">Filter dashboard by language</label>
                    <select id="language_category" name="language_category" class="form-control" onchange="this.form.submit()">
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
                        class="form-control"
                        value="{{ $dateRange->end?->format('Y-m-d') }}"
                        max="{{ now()->format('Y-m-d') }}"
                    >
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Apply Filter</button>
                @if($selectedLanguage || $dateRange->isActive())
                    <a href="{{ route('dashboard') }}" class="btn btn-ghost btn-sm">Clear Filters</a>
                @endif
            </form>
            @if($errors->has('start_date') || $errors->has('end_date'))
                <div class="dashboard-filter-error" role="alert">
                    {{ $errors->first('start_date') ?: $errors->first('end_date') }}
                </div>
            @endif
            <div style="font-size:.8rem;color:var(--gray-400);margin-top:.75rem;">
                Currently showing: <strong style="color:var(--gray-900);">{{ $hasFilters ? $filterLabel : 'All categories and languages (mixed)' }}</strong>
            </div>
        </div>
    </section>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon indigo">#</div>
            <div><div class="stat-value">{{ number_format($totalFeedbacks) }}</div><div class="stat-label">Total feedbacks</div></div>
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

    <div class="dashboard-grid analytics-grid">
        <section class="card-dark">
            <div class="card-header">
                <h2>Sentiment distribution</h2>
                <span class="chart-caption">Current dashboard filters</span>
            </div>
            <div class="card-body chart-panel chart-panel-doughnut">
                <canvas id="sentimentDistributionChart" aria-label="Positive, neutral, and negative feedback chart"></canvas>
            </div>
        </section>

        <section class="card-dark">
            <div class="card-header">
                <h2>Positive vs. negative trend</h2>
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
                <h2>Most critical concerns</h2>
                <p class="chart-caption" style="margin-top:.25rem;">Ranked by negative volume, negative ratio, confidence, and recency</p>
            </div>
            <span class="chart-caption">Top {{ $concernRankings->count() }}</span>
        </div>

        @if($concernRankings->isEmpty())
            <div class="empty-state"><h3>No ranked concerns yet</h3><p>Concern rankings appear after feedback has been analyzed.</p></div>
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
            <div class="card-header"><h2>Language classification</h2></div>
            <div class="card-body">
                @forelse($languageData as $language => $count)
                    <div style="display:flex;justify-content:space-between;gap:1rem;padding:.65rem 0;border-bottom:1px solid var(--gray-200);">
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
