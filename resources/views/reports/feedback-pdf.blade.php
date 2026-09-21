<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ResBack Feedback Analytics Report</title>
    <style>
        @page { margin: 34px 38px 48px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #172334; font-family: "DejaVu Sans", sans-serif; font-size: 9px; line-height: 1.45; }
        h1, h2, p { margin: 0; }
        .header { margin-bottom: 18px; padding-bottom: 12px; border-bottom: 2px solid #2e6da4; }
        .eyebrow { color: #2e6da4; font-size: 8px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        h1 { margin-top: 3px; color: #10243a; font-size: 19px; }
        .meta { margin-top: 7px; color: #607082; }
        .summary { width: 100%; margin-bottom: 18px; border-collapse: separate; border-spacing: 7px 0; }
        .summary td { width: 25%; padding: 10px; border: 1px solid #dce4ec; border-radius: 5px; background: #f6f8fb; }
        .summary .value { display: block; color: #10243a; font-size: 17px; font-weight: bold; }
        .summary .label { color: #68788a; font-size: 8px; text-transform: uppercase; }
        .section { margin-bottom: 17px; page-break-inside: avoid; }
        .section h2 { margin-bottom: 7px; color: #173652; font-size: 12px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { padding: 7px; background: #294760; color: #fff; font-size: 8px; text-align: left; }
        table.data td { padding: 7px; border-bottom: 1px solid #dce4ec; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #f7f9fb; }
        .two-column { width: 100%; border-collapse: separate; border-spacing: 14px 0; }
        .two-column > tbody > tr > td { width: 50%; padding: 0; vertical-align: top; }
        .muted { color: #68788a; }
        .score { font-weight: bold; }
        .negative { color: #b4232a; font-weight: bold; }
        .empty { padding: 12px; border: 1px solid #dce4ec; background: #f7f9fb; color: #68788a; }
        .footer-note { margin-top: 10px; color: #68788a; font-size: 8px; }
    </style>
</head>
<body>
    <header class="header">
        <div class="eyebrow">ResBack - CCIS</div>
        <h1>Feedback Analytics Report</h1>
        <p class="meta">
            Period: {{ $periodLabel }}
            @if($selectedLanguage) | Language: {{ $selectedLanguage }} @endif
            | Generated: {{ $generatedAt->format('M j, Y g:i A') }}
        </p>
    </header>

    <table class="summary">
        <tr>
            <td><span class="value">{{ number_format($feedbacks->count()) }}</span><span class="label">Total feedback</span></td>
            <td><span class="value">{{ number_format($sentimentData['positive']) }}</span><span class="label">Positive</span></td>
            <td><span class="value">{{ number_format($sentimentData['neutral']) }}</span><span class="label">Neutral</span></td>
            <td><span class="value">{{ number_format($sentimentData['negative']) }}</span><span class="label">Negative</span></td>
        </tr>
    </table>

    <table class="two-column">
        <tr>
            <td>
                <section class="section">
                    <h2>Sentiment distribution</h2>
                    <table class="data">
                        <thead><tr><th>Sentiment</th><th>Count</th><th>Share</th></tr></thead>
                        <tbody>
                        @foreach($sentimentData as $sentiment => $count)
                            <tr>
                                <td>{{ ucfirst($sentiment) }}</td>
                                <td>{{ number_format($count) }}</td>
                                <td>{{ $feedbacks->count() ? number_format(($count / $feedbacks->count()) * 100, 1).'%' : '0.0%' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </section>
            </td>
            <td>
                <section class="section">
                    <h2>Language classification</h2>
                    @if($languageData->isEmpty())
                        <div class="empty">No language classifications in this period.</div>
                    @else
                        <table class="data">
                            <thead><tr><th>Language</th><th>Count</th></tr></thead>
                            <tbody>
                            @foreach($languageData as $language => $count)
                                <tr><td>{{ $language }}</td><td>{{ number_format($count) }}</td></tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </section>
            </td>
        </tr>
    </table>

    <section class="section">
        <h2>Leading concerns</h2>
        @if($concernRankings->isEmpty())
            <div class="empty">No ranked concerns in this period.</div>
        @else
            <table class="data">
                <thead><tr><th style="width:7%;">Rank</th><th>Concern</th><th>Critical score</th><th>Negative</th><th>Total reports</th><th>Latest report</th></tr></thead>
                <tbody>
                @foreach($concernRankings as $ranking)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $ranking['topic'] }}</td>
                        <td class="score">{{ number_format($ranking['critical_score'], 1) }}</td>
                        <td>{{ number_format($ranking['negative_count']) }} ({{ number_format($ranking['negative_ratio'] * 100, 0) }}%)</td>
                        <td>{{ number_format($ranking['total_count']) }}</td>
                        <td>{{ $ranking['latest_feedback_at']?->format('M j, Y') ?? '-' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <section class="section">
        <h2>Critical negative feedback requiring review</h2>
        @if($criticalFeedbacks->isEmpty())
            <div class="empty">No negative feedback in this period.</div>
        @else
            <table class="data">
                <thead><tr><th style="width:15%;">Date</th><th>Feedback</th><th style="width:16%;">Confidence</th><th style="width:16%;">Language</th></tr></thead>
                <tbody>
                @foreach($criticalFeedbacks as $feedback)
                    <tr>
                        <td>{{ $feedback->created_at->format('M j, Y') }}</td>
                        <td>{{ $feedback->content }}</td>
                        <td class="negative">{{ number_format(($feedback->sentimentResult?->confidence ?? 0) * 100, 1) }}%</td>
                        <td>{{ $feedback->sentimentResult?->language_category ?? 'Unclassified' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <section class="section">
        <h2>Analysis processing status</h2>
        <table class="data">
            <thead><tr><th>Status</th><th>Count</th></tr></thead>
            <tbody>
            @forelse($statusData as $status => $count)
                <tr><td>{{ ucfirst($status) }}</td><td>{{ number_format($count) }}</td></tr>
            @empty
                <tr><td colspan="2">No feedback in this period.</td></tr>
            @endforelse
            </tbody>
        </table>
        <p class="footer-note">Resolution status is not included because ResBack currently tracks analysis processing status only.</p>
    </section>
</body>
</html>
