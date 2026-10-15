# Feedback analysis queue plan

Date: 2026-10-15 (Asia/Manila)

Status: proposal for discussion. No application code, database, runtime settings, or deployment changes have been made for this plan.

## Goal and scope

Accept and durably save feedback without making the student wait for AI analysis. Process accepted feedback in the background at a controlled rate, retry recoverable errors, and show accurate progress and final outcomes.

A queue reduces exposure to request timeouts and makes recovery possible. It does not guarantee AI availability, increase the provider quota, or ensure an immediate result. Submission still depends on the application and database being available.

Preserve existing validation, per-user submission limits, duplicate-content checks, rejection rules, privacy boundaries, and completed result presentation. Rejected feedback must never enter the analysis queue. No emails or external notifications are proposed.

## Current code findings

- `app/Http/Controllers/FeedbackController.php`: saves accepted feedback as `pending`, then calls `SentimentService::analyze()` inside the submission request.
- `app/Services/SentimentService.php`: uses a 10-second connection timeout, a 45-second HTTP timeout, and an HTTP retry setting. Handled failures are saved as `failed` and returned normally. A queued wrapper alone would therefore not activate queue retries for these failures.
- The service stores a sentiment result, marks feedback `analyzed`, and then rebuilds category rankings. A ranking failure must not cause a successful AI analysis to run again.
- `config/queue.php` and the jobs migration provide database queue infrastructure in source. Runtime configuration and migrated tables still need verification before implementation.
- `resources/views/feedback/result.blade.php`: currently describes missing results as an analysis failure, including genuinely pending work.
- `resources/views/feedback/history.blade.php`: can display a Pending sentiment badge even when the feedback status is failed.
- `Feedback::scopeReportable()` excludes rejected feedback but includes pending and failed records. Reports need an explicit distinction between submitted counts and analyzed sentiment totals.
- `sentiment_results.feedback_id` has a foreign key but no unique constraint in the inspected migrations.
- `ClassifyFeedbackLanguages` calls the service directly and can select rejected records with no analysis. This path must follow the same rejection, locking, and rate-limit rules.

## Proposed submission and processing flow

1. Validate and apply the existing submission/rejection checks.
2. For accepted feedback, persist both feedback and durable analysis work before confirming success. For the initial database queue, use the same database connection and one transaction for the feedback, analysis tracking, and job insertion. The worker must only see committed work. Do not rely solely on a best-effort enqueue after saving the feedback.
3. Commit, then redirect to an authenticated, owner-only feedback detail/result page. Refreshing this GET page must not submit another feedback.
4. A dedicated `feedback-analysis` queue processes feedback IDs. Read feedback from the database when the job runs; avoid copying text or student identity into queue payloads.
5. Before calling AI, verify the record still exists, is accepted, is not already successfully analyzed, and belongs to the current analysis run. Claim it so another job cannot process it concurrently.
6. Set the internal phase to processing, call the provider, and validate the response.
7. On success, atomically store one result and mark feedback analyzed. Trigger a separately recoverable ranking refresh after the result is committed.
8. On a recoverable failure, schedule another attempt and show retrying. On a permanent failure or exhausted retry budget, mark analysis failed and expose admin recovery.

If implementation cannot make feedback and queue insertion atomic on the same connection, use a durable database outbox/recovery record. A feedback record must not be left without recoverable work because the process stopped between saving and enqueueing.

## Status model and student messages

Keep the existing top-level statuses `pending`, `analyzed`, `failed`, and `rejected`. Add analysis tracking for the pending phases rather than expanding the existing status enum immediately.

- **Waiting for analysis**: pending, queued phase. Message: "Your feedback is saved and waiting for analysis. You can leave this page and check My Feedback later."
- **Analyzing**: pending, processing phase. Message: "Your feedback is being analyzed."
- **Retrying analysis**: pending, retrying phase. Message: "Analysis is temporarily delayed. We will try again automatically. Your feedback is saved."
- **Analysis complete**: analyzed, with a valid stored result. Message: "Your feedback has been analyzed." Show the existing sentiment, confidence, language, and keywords.
- **Analysis needs attention**: failed. Message: "Your feedback is saved, but analysis could not be completed. An administrator can retry it. You do not need to submit it again."
- **Rejected**: keep the current rejection explanation and respectful-rephrasing guidance. Do not enqueue or offer analysis retry.

Suggested tracking fields: phase, analysis run identifier, actual provider-attempt count, queued/started/completed timestamps, next retry time, first-attempt time, sanitized error category, and last-progress time. Finalize the smallest schema that supports recovery and an auditable retry history during implementation. Use database time consistently; display user-facing times in Asia/Manila.

Do not show invented percentages, guaranteed completion times, or an exact queue position. Those can become misleading when retries and provider throttling change the processing order.

## How students see updates

- Keep the current page layout; add a compact status label and appropriate message on the result/detail page and each history row.
- Add an owner-authorized status endpoint under the existing authenticated, active, feedback-submitter protections. Do not expose other students' feedback or identities through IDs or status responses.
- Poll the visible result page approximately every 5 seconds while work is pending. For history, check only the visible pending records in one request, not one request per row.
- Pause polling when the tab is hidden; resume on return. Stop when all visible records reach final states. Back off polling after network/server errors and stop on expired authentication or forbidden access.
- If the browser loses connectivity, say "Unable to refresh status. Your last saved status is shown." A polling failure must not change the analysis status to failed.
- After two minutes of active waiting, slow polling to approximately every 15 seconds and show "Analysis is taking longer than usual. You can check back later."
- Provide a Refresh status control and a My Feedback link. With JavaScript disabled, reloading the detail page or opening history must show the current persisted state.
- Announce status changes accessibly without moving focus or repeatedly announcing unchanged content.
- Closing the browser must not cancel queued work. Completion can be reviewed from history after signing in again.

## Retry policy: proposed starting values

These values are initial application policy, not provider capacity claims. Tune them after quota checks and representative measurements.

- Allow **four actual AI attempts total** per analysis run: the first attempt plus three retries.
- Delays after recoverable failures: **30 seconds, 2 minutes, and 5 minutes**, with a small random addition to spread traffic. Measure each delay from the preceding failed attempt.
- Keep a **10-second connection timeout** and **45-second request timeout** per AI attempt initially.
- Remove nested immediate HTTP retries from the queued path: one provider call per job execution makes the actual retry budget predictable.
- Apply a **20-minute retry window starting with the first actual AI attempt**, so time spent waiting behind other feedback does not expire an untouched submission. Stop when either the actual-attempt budget or retry window is exhausted. Do not combine Laravel settings in a way that accidentally makes one limit override the other; enforce and test both in application tracking.
- Start with a **60-second job timeout** and **120-second reservation expiry (`retry_after`)**, subject to measured database overhead and verification that the runtime can enforce worker timeouts. Keep the HTTP timeout below the job timeout and the job timeout safely below the reservation expiry.
- Rate-limit/lock deferrals before an AI call must not consume the four-call budget. Bound repeated job reservations separately to detect stuck work; Laravel's raw job-attempt count is not the same as actual provider attempts.

Failure classification:

- Connection failures, timeouts, temporary provider 5xx errors, and recoverable 429 responses: retry within the budget.
- For 429 or an explicit provider cooldown, honor a valid Retry-After value when longer than the normal delay. Share the cooldown across the queue instead of immediately sending the next student's request. If the required cooldown exceeds the remaining retry window, stop that run and make it recoverable by an admin later.
- Missing/invalid credentials, forbidden access, unsupported model, invalid request configuration, or a known exhausted long-term quota: fail without repeatedly calling the provider. Raise an operational alert; pause AI consumption for systemic configuration problems while continuing to save new submissions as queued.
- Invalid or unusable model output: allow at most one additional AI attempt for that error category, still within the four-call budget. Never invent sentiment, confidence, language, or keywords.
- Transient database failures while saving a validated result: retry persistence where feasible. If a crash forces another provider call, preserve the one-result guarantee; external AI calls cannot be guaranteed exactly once.
- Ranking refresh failure after successful analysis: retry ranking refresh separately. Keep the feedback analyzed and do not call AI again for that failure.

Change the service contract so callers can distinguish success, retryable failure, and permanent failure. Expected transient failures must not be swallowed as apparent successful jobs. All terminal paths, including exhausted jobs and worker crashes, must converge on accurate persisted status through failure hooks or recovery.

## Queue capacity and worker operation

- Start with the database queue and **one dedicated analysis worker**. This means one analysis job runs at a time initially; other accepted submissions remain saved and queued.
- A single worker still needs a shared provider rate limiter. Concurrency alone does not enforce requests-per-minute or token limits. Use the active model/project quota with headroom, a shared limiter/cooldown, and representative feedback sizes.
- Confirm the active provider quotas and hosting support before choosing the production launch rate. Do not treat 20 users as 20 permitted simultaneous AI calls.
- Supervise the production worker so it restarts after crashes and deployments. Verify a working scheduler/recovery process as well.
- Local Windows testing must keep a worker running separately from the web server. Verify timeout support in the chosen runtime; do not assume a Windows PHP worker enforces Linux-style process timeouts. Crash/timeout acceptance must also run in the intended production runtime.
- Monitor worker heartbeat, pending count, oldest queued age, retry count, terminal failure count, and provider latency/error categories. Use a starting alert threshold of no heartbeat for two minutes or oldest waiting work above ten minutes, then tune after the load test. Account for intentional worker pauses.
- A stopped worker leaves feedback waiting. A scheduled recovery check must identify expired processing claims and recover orphaned pending work without creating concurrent analyses.

## Duplicate protection, deletion, and recovery

- Treat queue delivery as potentially repeated. Use a per-feedback analysis claim/run token plus a database unique constraint on `sentiment_results.feedback_id`.
- Before adding that constraint, inspect existing duplicate results and report any conflict. Do not silently delete historical analysis data to make the migration pass.
- Atomically save result and final status. Successful jobs retried after an acknowledgement failure must detect the existing completed result and return without another AI call.
- Admin retries create a new run identifier and reset that run's retry budget. Old jobs must not overwrite a newer run's state. Preserve prior error/attempt history for diagnosis.
- If feedback is deleted before execution, skip it. If deletion happens during an AI call, recheck before saving; do not recreate the feedback or its result. Recheck rejection/state before committing a result as well.
- Protect ranking refreshes per category before adding more workers; the current ranking rebuild computes data and then replaces stored category rankings.
- Route backfill/language-classification work through compatible queue rules. Exclude rejected feedback, avoid competing analyses, preserve existing successful results when only enrichment is needed, and prevent administrative backfills from starving new submissions.
- Recovery uses durable run/claim information and locks, not only record age. Never blindly reset every old pending record or retry every failed job.

## Administrator experience

- Add a restricted analysis-status filter and a concise operational summary for existing admin/super-admin roles. Keep reviewer identity restrictions intact.
- Show waiting, processing, retrying, completed, and failed counts, plus a sanitized failure category, actual attempts, and last update for the selected feedback.
- Provide **Retry analysis** for failed accepted feedback. Require authorization, CSRF protection, throttling, and an atomic check so double-clicks cannot create overlapping runs.
- A retry returns the record to waiting and updates the student's status automatically. No resubmission is needed.
- Do not offer retry for rejected, deleted, completed, or currently active runs in the initial scope. A deliberate future reanalysis feature would be separate.
- Technical details belong in restricted logs/operations views. Students must not see API keys, raw provider error bodies, internal exceptions, or queue implementation details.

## Reports and dashboards

- Separate total accepted submissions from completed analyses. Pending/retrying/failed records must not be counted as neutral sentiment or given invented language/keyword values.
- Show consistent status labels in student history and reviewer/admin lists instead of treating all missing results as pending.
- Define sentiment percentages and rankings using valid completed analyses; label pending/failed analysis counts where totals would otherwise be confusing. Preserve each view's existing visibility rules.
- Make ranking-refresh delay/failure observable to administrators without downgrading a completed analysis.

## Implementation phases after approval

1. **Durable data and state:** verify runtime/tables, inspect duplicate results, add tracking/uniqueness safely, implement state transitions and atomic enqueue. Review migration compatibility with the chosen production database.
2. **Analysis jobs and retries:** introduce classified outcomes, controlled provider calls, bounded retries/cooldowns, idempotent saving, and separately recoverable rankings. Align the existing classification command.
3. **Student status experience:** add the owner-only detail/status routes, redirect after submission, update existing views, and add bounded polling/manual refresh.
4. **Admin recovery and operations:** add status filters/retry controls, monitoring, worker supervision, and stuck/orphan recovery. Document local and production start/restart/recovery procedures.
5. **Acceptance and rollout:** run meaningful automated tests and real-worker burst/crash tests, deploy with supervision, and monitor initial usage before increasing concurrency. Review existing pending/failed records before an explicitly bounded backfill; do not bulk retry them automatically.

Each phase needs its relevant checks. Do not switch the live submission path to queued processing until workers, status visibility, and recovery are ready together.

## Acceptance criteria

- A burst of **20 valid, distinct users** produces 20 saved accepted feedback records and 20 durable analysis runs. Submission requests make no AI calls.
- With the worker stopped, those submissions are confirmed as saved and waiting. Restarting the worker drains the backlog without resubmission.
- With a healthy simulated provider, all 20 finish with exactly one persisted result per feedback. First-pass concurrency remains one and the configured aggregate rate limit is respected.
- With controlled temporary failures, the correct status progresses waiting -> analyzing -> retrying -> analyzing -> completed, or needs attention after its budget. Four actual provider calls cannot be exceeded per run.
- Verify 429 cooldown/Retry-After, transient 5xx, timeouts, configuration errors, invalid JSON, persistence failures, and ranking-only failures. A delayed retry must not block other eligible jobs from progressing, except during an intentional provider-wide cooldown.
- Kill a worker during processing and after result commit but before job acknowledgement. Recovery leaves consistent status and at most one stored analysis result; measure any unavoidable repeated external calls.
- Repeated job delivery and repeated admin retry clicks do not create overlapping active runs. Deleted/rejected feedback is never analyzed or resurrected.
- Students cannot read or poll another student's feedback. Faculty cannot invoke admin retry. Polling returns only authorized data and never changes state.
- Result/history/dashboard/report labels agree. Terminal failures never show a Pending sentiment badge. Pending analyses never display a fake neutral result.
- Closing/reopening the page, disabling JavaScript, hiding a tab, losing connectivity, and session expiry produce accurate, usable behavior.
- Proposed submission target: **95% of the 20 burst requests receive a saved confirmation within 2 seconds** in the agreed test environment, excluding AI work. This is a target to measure, not an existing performance claim. Record hardware/runtime, database, API simulation/live mode, backlog drain time, and error rate.
- Use provider fakes for deterministic retry/error tests and a real queue worker for delivery/restart tests. A small live-provider check follows quota review; do not run a costly live burst by default.

## Decisions to finalize before implementation

- Confirm database queue + one analysis worker as the initial design.
- Confirm the four-attempt retry policy, delays, 20-minute retry window, and admin-only manual retry.
- Confirm the suggested student wording and polling behavior within the existing layouts.
- Confirm production hosting can supervise a persistent worker and run scheduled recovery, then choose a numeric provider rate budget from the active project/model quota. This is a production dependency, not a reason to guess capacity.

## References

- Existing project context: `PRODUCTION_READINESS_PLAN.md`, especially AI outage recovery and queued-analysis planning.
- Laravel 13 queue documentation: https://laravel.com/framework/docs/13.x/queues
- Worker timeout/reservation guidance: https://laravel.com/framework/docs/13.x/queues#job-expirations-and-timeouts
- Job retry limits: https://laravel.com/framework/docs/13.x/queues#specifying-max-job-attempts-timeout-values

Laravel documents database-backed queues, retry/backoff controls, rate limiting, failed-job recovery, and the need for job timeouts to be shorter than reservation expiry. This document's numeric settings and interface behavior are proposed ResBack policies, not Laravel defaults or verified production capacity.
