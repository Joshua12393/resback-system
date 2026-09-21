<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Feedback;
use App\Services\ConcernRankingService;
use App\Services\LanguageCategoryService;
use App\Support\FeedbackDateRange;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FeedbackReportController extends Controller
{
    public function __invoke(Request $request, ConcernRankingService $rankingService): Response
    {
        $dateRange = FeedbackDateRange::fromRequest($request);
        $selectedLanguage = $request->filled('language_category')
            ? $request->string('language_category')->toString()
            : null;
        abort_if(
            $selectedLanguage && ! in_array($selectedLanguage, LanguageCategoryService::CATEGORY_LABELS, true),
            404,
            'The selected language is unavailable.'
        );

        $category = Category::ccis();
        $feedbacks = $dateRange->apply(Feedback::query()
            ->with(['category', 'sentimentResult'])
            ->where('category_id', $category->id)
            ->when($selectedLanguage, fn ($query) => $query->whereHas(
                'sentimentResult',
                fn ($resultQuery) => $resultQuery->where('language_category', $selectedLanguage)
            )))
            ->latest()
            ->get();

        $sentimentData = [
            'positive' => $feedbacks->where('sentimentResult.sentiment', 'positive')->count(),
            'neutral' => $feedbacks->where('sentimentResult.sentiment', 'neutral')->count(),
            'negative' => $feedbacks->where('sentimentResult.sentiment', 'negative')->count(),
        ];
        $languageData = $feedbacks
            ->pluck('sentimentResult.language_category')
            ->filter()
            ->countBy()
            ->sortDesc();
        $statusData = $feedbacks->countBy('status')->sortDesc();
        $concernRankings = $rankingService
            ->rank($category->id, $selectedLanguage, $dateRange)
            ->take(10)
            ->values();
        $criticalFeedbacks = $feedbacks
            ->filter(fn (Feedback $feedback) => $feedback->sentimentResult?->sentiment === 'negative')
            ->sortByDesc(fn (Feedback $feedback) => [
                $feedback->sentimentResult?->confidence ?? 0,
                $feedback->created_at->timestamp,
            ])
            ->take(10)
            ->values();

        $html = view('reports.feedback-pdf', [
            'feedbacks' => $feedbacks,
            'sentimentData' => $sentimentData,
            'languageData' => $languageData,
            'statusData' => $statusData,
            'concernRankings' => $concernRankings,
            'criticalFeedbacks' => $criticalFeedbacks,
            'periodLabel' => $dateRange->label(),
            'selectedLanguage' => $selectedLanguage,
            'generatedAt' => now(),
        ])->render();

        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->getCanvas()->page_text(500, 810, 'Page {PAGE_NUM} of {PAGE_COUNT}', null, 8, [0.35, 0.4, 0.46]);

        $dateSuffix = $dateRange->isActive()
            ? $dateRange->start->format('Y-m-d').'-to-'.$dateRange->end->format('Y-m-d')
            : now()->format('Y-m-d-His');

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="resback-feedback-report-'.$dateSuffix.'.pdf"',
        ]);
    }
}
