# ResBack production readiness plan

Date: October 11, 2026  
Status: Saved for later discussion. Planning only; implementation and deployment have not started under this plan.

## Purpose and boundaries

Prepare ResBack for secure, dependable online operation across security, shipping, speed, traffic, and recovery. Frontend/backend completeness and production readiness must be verified separately.

This plan does not authorize application changes, purchases, hosting setup, production migrations, commits, or deployment. Discuss the decisions below before implementation. Preserve existing UI work, local data, `config/feedback.php`, temporary files, and unrelated changes. Reinspect the checkout before starting any phase.

Use one Laravel application initially. Additional infrastructure should answer a measured need. No capacity, uptime, provider price, or completion date is promised by this plan.

## Inspection baseline

Source inspection on October 11, 2026 found:

- Laravel dependency constraint `^13.17` and PHP requirement `^8.3` in `composer.json`; installed versions and host compatibility still need verification.
- Session authentication, password hashing, session regeneration, role middleware, and active-account checks.
- Personal feedback history scoped through the authenticated user's feedback relationship. Existing tests cover authentication, account management, feedback privacy/protection, exports, and other workflows; they were not rerun for this planning pass.
- Feedback submission protection: five submissions per user within a ten-minute window, plus normalized duplicate checks within the previous 24 hours. Explicit login/registration throttling was not found in the inspected routes/controllers.
- Accepted feedback is saved before synchronous sentiment analysis. The AI HTTP request has connection/request timeouts, retries, logging, and failed-state handling. Durable background processing is not implemented in that submission path.
- Database-backed cache, sessions, and queue are defaults in the example/configuration files. These defaults do not prove the current runtime or production configuration.
- A framework health route at `/up` and configurable Laravel log channels. No centralized production monitoring or restore drill was established by this inspection.
- No `.github` workflow directory in this checkout.
- README describes Render/Supabase as a proposed architecture, including profile-photo storage integration that is not implemented. Provider choice remains open. Prior PostgreSQL compatibility findings must be rechecked against current migrations.

These are inspection findings, not a completed security audit, test report, or confirmation of a live deployment.

## Phase 0 — Agree on launch requirements

Priority: prerequisite for infrastructure and service commitments.

Decide:

- Defense demonstration, limited pilot, or ongoing school service.
- Expected registered users, peak concurrent users, submission bursts, report sizes, and operating hours.
- Hosting budget, billing owner, domain owner, deployment operator, and incident owner.
- Acceptable data loss (recovery point objective, RPO) and restoration time (recovery time objective, RTO).
- Registration eligibility, school-email verification, password recovery, and administrator MFA requirements.
- Who may see feedback content, account identity, exports, and administrative activity.
- Feedback retention/deletion rules and what may be sent to the AI provider.

Completion check: record approved answers and measurable launch criteria. Leave unknowns explicit; do not invent workload or budget values.

## Phase 1 — Security

Priority: launch blocker.

### Auth and permissions

- Create a role/action matrix for student, faculty, admin, and super admin, using current intended behavior as the starting point.
- Verify permissions on the server for dashboard access, exports, feedback deletion, profile updates, account status/role changes, and account deletion.
- Review ownership checks, route-model binding, mass assignment, role escalation, inactive sessions, and sensitive information returned in responses.
- Implement agreed account verification/recovery and privileged-account protections.
- Confirm feedback privacy wording. Records currently have `user_id`; distinguish confidentiality from anonymity and document authorized identity access.

Completion checks: direct requests from unauthorized roles fail; one user cannot retrieve another user's private records; disabled accounts lose access; role changes cannot bypass hierarchy rules; agreed recovery flows work without leaking account existence unnecessarily.

### Security and RLS

- Review CSRF coverage, escaping/XSS, query handling, upload validation and access, session cookies, trusted proxies/HTTPS, security headers, dependency advisories, and secret exposure.
- Keep credentials out of source, browser bundles, logs, exports, and artifacts. Define secret rotation and preserve the application encryption key across releases.
- Use separate staging/production credentials and least-privilege database access where feasible.
- Choose the database authorization model after the database provider is selected. Laravel authorization remains necessary even if database RLS is added.
- If using Supabase, review Data API exposure, schema grants, storage policies, database roles, and RLS. A Laravel session does not automatically provide Supabase user identity; privileged database roles can bypass RLS. If user-level RLS is required, design identity propagation and connection handling explicitly.

Completion checks: production debug output is disabled; secrets remain server-side; uploads reject invalid content; private data is inaccessible through unintended storage/API paths; database security is tested through each actual access path, including any bypass role.

### Rate limiting

- Add login and registration abuse controls with account/IP-aware limits that do not unnecessarily penalize shared campus networks.
- Review feedback throttling under simultaneous requests; separate duplicate detection from retry/idempotency behavior.
- Limit expensive exports and AI work. Select shared limiter storage if multiple application instances are introduced.
- Return understandable retry guidance and log meaningful abuse signals without excessive personal data.

Completion checks: repeated and concurrent abuse is limited; legitimate retries do not create duplicate records or repeated AI charges; agreed limits work consistently across the intended deployment.

## Phase 2 — Shipping

Priority: launch blocker. Depends on Phase 0 and the core controls in Phase 1.

### Hosting/deployment and cloud/compute

- Compare Laravel-compatible hosting against budget, supported PHP/extensions, region, database latency, worker/scheduler support, sleep behavior, storage persistence, backups, and operating effort. Verify current provider documentation and pricing before selection.
- Choose MySQL or PostgreSQL. If PostgreSQL is selected, validate the complete migration chain, queries, indexes, and rollback behavior in a disposable database before touching real data.
- Define web runtime, database, uploaded-file storage, cache/session store, and worker requirements. Choose persistent/shared upload storage appropriate to the host.
- Build a reproducible release using locked dependencies and built Vite assets. Configure production environment, HTTPS, logs, cache optimization, file permissions, and health checks.
- Use staging with synthetic or anonymized data. Plan safe database migrations, maintenance windows if needed, release validation, and rollback compatibility.
- Document deployment commands and secret names without recording secret values. Never regenerate `APP_KEY` as a routine deployment step.

Completion checks: a clean deployment can be reproduced; migrations pass on the chosen database; login, submission, dashboard, exports, and uploads work on the hosted environment; redeploy/restart preserves data and photos; rollback has been rehearsed.

### CI/CD and version control

- Define review/merge rules, release tags, deployment permissions, and separate staging/production environments.
- Add CI for relevant existing tests, code style, frontend production build, and dependency/security checks with a clear failure policy.
- Run integration checks against the intended database engine, not only a convenient local substitute.
- Deploy a traceable commit after checks succeed; include post-deployment smoke checks and a manual production gate initially.
- Preserve unrelated working-tree changes. Split future implementation commits by module/action, and confirm branch/remote delivery instructions before Git operations.

Completion checks: failing required checks prevent release; each deployment identifies its commit; secrets are isolated; staging validation and production rollback are documented and repeatable.

## Phase 3 — Survival

Priority: launch blocker. Prepare before public launch, not after an incident.

### Error tracking and logs

- Choose log destinations, rotation/retention, error tracking, and alert recipients based on budget and privacy.
- Track request failures, AI failures/latency, database errors, queue backlog/failed jobs if introduced, disk/storage pressure, and deploy health.
- Use request/job identifiers to connect events. Scrub passwords, keys, session tokens, email addresses where unnecessary, and confidential feedback from telemetry.
- Distinguish security audit events from diagnostic logs. Decide which role/account changes, exports, and destructive actions require an audit trail and who can access it.

Completion checks: a controlled failure creates a useful scrubbed event and reaches the agreed operator; logs survive the host lifecycle as required; alerts distinguish actionable incidents from routine noise.

### Availability and recovery

- Monitor the application externally. Extend readiness checks as needed for database/storage and worker operation; `/up` alone does not prove every dependency is healthy.
- Configure database and upload backups, access protection, retention, and a separate failure domain where feasible. Identify who owns each backup.
- Restore database and uploaded files into an isolated environment and measure actual recovery time/data loss against agreed RTO/RPO.
- Document outage handling, compromised-secret response, rollback, failed migrations, AI outages, and contact/ownership information.
- Ensure an AI outage does not lose submitted feedback. Define how failed analysis is retried and how users see its status.

Completion checks: a restore drill recovers required data and photos; the system remains usable within the agreed degraded behavior; the operator can follow the incident runbook without guessing.

## Phase 4 — Speed

Priority: measure before optimizing; resolve unacceptable launch latency before release.

### Background analysis

- Measure submission latency and AI wait time. Design durable queued analysis if needed for the intended launch workload.
- Preserve feedback before dispatch, use safe transaction/dispatch ordering, and make processing idempotent.
- Add pending/completed/failed UI states, bounded retries/backoff, provider concurrency limits, failed-job recovery, and worker supervision.
- Align worker/job timeouts with provider timeouts and retry behavior to prevent concurrent processing of the same job.

Completion checks: slow/unavailable AI does not hold the submission request open or lose records; retries do not duplicate analysis/rankings; workers recover after restart; the UI reports status accurately.

### Caching and CDN

- Measure slow dashboard queries and report generation; improve query plans, indexes, eager loading, and pagination first where appropriate.
- Cache suitable aggregates with defined expiration and invalidation after feedback/analysis/deletion changes.
- Use versioned static assets and evaluate CDN delivery when it benefits actual usage.
- Keep private responses/exports out of shared caches. Scope any user/role-dependent cache keys correctly.

Completion checks: agreed latency targets pass; aggregate freshness is acceptable; two different users/roles cannot receive each other's private cached content; deployment refreshes static assets correctly.

## Phase 5 — Traffic

Priority: workload-dependent. Load testing belongs before launch; additional servers require evidence.

### Load balancing and scaling

- Run staged load tests using synthetic data and capped/mocked AI calls unless a controlled real-provider test is explicitly agreed.
- Measure throughput, latency percentiles, failure rate, database connections, memory/CPU, and queue depth under the agreed peak and burst workload.
- Address measured bottlenecks through query improvements, worker capacity, or a larger instance before adding application replicas when appropriate.
- If multiple web instances are needed, introduce load balancing, shared sessions/cache/limits, shared uploads, consistent secrets, health checks, and coordinated deployments/migrations.
- Scale AI workers within provider quotas and budget. Prevent duplicate scheduled tasks and duplicate job execution.

Completion checks: the agreed workload passes without lost/duplicate submissions or privacy violations; instance restart behavior is acceptable; shared state works across replicas; scaling and cost limits are documented.

## Release gate

Release only after the agreed launch-critical checks pass:

- Role/ownership and abuse-protection verification.
- Hosted end-to-end workflows, selected-database compatibility, and persistent storage.
- Successful CI and a traceable deploy with rehearsed rollback.
- Working monitoring/alerts and a successful backup restore drill.
- Acceptable performance under the agreed workload and documented AI failure behavior.
- Named operational owner, known ongoing cost, and unresolved risks accepted explicitly.

A defense demonstration may use lighter operational commitments than ongoing school use, but still needs protection of real accounts/data and a dependable demonstration/recovery procedure. Advanced CDN, replicas, or autoscaling are conditional, not automatic launch requirements.

## Later discussion agenda

1. Confirm demonstration versus ongoing service, users/traffic, budget, and owners.
2. Agree on permissions, confidentiality/identity access, registration, recovery, and MFA.
3. Choose hosting/database/storage and decide whether database RLS is needed.
4. Agree on CI/CD, staging, migration safety, and rollback.
5. Set logging/alerting, retention, backups, RPO, and RTO.
6. Decide queued AI behavior and measurable speed targets.
7. Review load-test evidence before choosing cache/CDN/extra servers.
8. Convert approved decisions into implementation tasks with estimates and acceptance checks.

No reminder or recurring follow-up is scheduled by saving this plan.

## References to recheck during implementation

- Laravel deployment: https://laravel.com/framework/docs/deployment
- Laravel rate limiting: https://laravel.com/framework/docs/rate-limiting
- Laravel queues: https://laravel.com/framework/docs/13.x/queues
- Supabase RLS: https://supabase.com/docs/guides/database/postgres/row-level-security

References were consulted for the preceding discussion. Recheck current documentation, provider capabilities, security advisories, and pricing when implementation begins.
