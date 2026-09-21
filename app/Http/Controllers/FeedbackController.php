<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeedbackRequest;
use App\Models\Category;
use App\Models\Feedback;
use App\Services\FeedbackAutoRejectService;
use App\Services\FeedbackSubmissionGuard;
use App\Services\SentimentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function __construct(
        protected SentimentService $sentimentService,
        protected FeedbackSubmissionGuard $submissionGuard,
        protected FeedbackAutoRejectService $autoRejectService,
    ) {}

    /**
     * Show the confidential feedback submission form.
     */
    public function create(): View
    {
        $defaultCategory = Category::ccis();

        return view('feedback.create', compact('defaultCategory'));
    }

    /**
     * Store a newly submitted feedback and trigger sentiment analysis.
     */
    public function store(StoreFeedbackRequest $request): View
    {
        $user = $request->user();

        if ($this->submissionGuard->isDuplicate($user, $request->integer('category_id'), $request->string('content')->toString())) {
            throw ValidationException::withMessages([
                'content' => 'You already submitted the same feedback within the last 24 hours.',
            ]);
        }

        if ($this->submissionGuard->tooManyAttempts($user)) {
            $minutes = max(1, (int) ceil($this->submissionGuard->availableIn($user) / 60));

            throw ValidationException::withMessages([
                'content' => "You have submitted several feedbacks recently. Please try again in {$minutes} minute(s).",
            ]);
        }

        // Hash IP for rate limiting — never stored as plain text
        $ipHash = hash('sha256', $request->ip().config('app.key'));

        $shouldReject = $this->autoRejectService->shouldReject($request->string('content')->toString());

        $feedback = Feedback::create([
            'user_id' => $user->id,
            'category_id' => $request->category_id,
            'content' => $request->content,
            'ip_hash' => $ipHash,
            'status' => $shouldReject ? 'rejected' : 'pending',
        ]);

        $this->submissionGuard->recordSubmission($user);

        if ($shouldReject) {
            return view('feedback.result', ['feedback' => $feedback]);
        }

        // Trigger sentiment analysis (runs synchronously; swap for a queued job later)
        $this->sentimentService->analyze($feedback);

        return view('feedback.result', ['feedback' => $feedback->load('sentimentResult')]);
    }

    /**
     * Show only the signed-in user's feedback submissions.
     */
    public function history(Request $request): View
    {
        $feedbacks = $request->user()
            ->feedbacks()
            ->with(['category', 'sentimentResult'])
            ->latest()
            ->simplePaginate(10);

        return view('feedback.history', compact('feedbacks'));
    }

    /**
     * Show the thank-you confirmation page.
     */
    public function thankyou(): View
    {
        $feedback = null;
        if (session()->has('last_feedback_id')) {
            $feedback = Feedback::with('sentimentResult')->find(session('last_feedback_id'));
        }

        return view('feedback.thankyou', compact('feedback'));
    }
}
