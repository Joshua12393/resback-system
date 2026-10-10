# ResBack UI/UX implementation plan

Date: October 11, 2026

Status: Implementation started following the user's request to start building. The user authorized moving directly into the web UI; the earlier mockup-first gate is superseded for this implementation pass.

## 1. Agreed direction

Friendly, minimal, and responsive, with Duolingo-inspired subtle interaction feedback and consistent light/dark themes. This updated preference supersedes the earlier strictly professional visual treatment; analytics still prioritize clarity and readability.

- Dashboard reference: user-supplied Quicken screenshot from Mobbin. Adapt its neutral background, white surfaces, restrained accents, readable charts, and generous spacing to feedback analytics.
- Feedback and account reference: user-supplied Buffer screenshot from Mobbin. Adapt its focused centered composition, clear heading, and obvious primary action.
- Interaction and personality reference: Duolingo. Use approachable rounded shapes, tactile buttons, encouraging plain language, and brief motion tied to user actions. Quicken and Buffer remain layout references; Duolingo guides the friendly visual treatment and motion.
- Reference reading: [Duolingo's shape language](https://blog.duolingo.com/shape-language-duolingos-art-style/). The recommendations below are ResBack design proposals, not exact Duolingo specifications.
- Retain ResBack identity and indigo accents. A mascot is optional future design work, not a prerequisite. Streaks, points, leaderboards, sounds, and incentives for repeated feedback are outside scope.
- Use references as visual guidance; retain ResBack branding and actual workflows. The Buffer questionnaire does not imply adding a questionnaire or multiple steps.
- Keep Laravel Blade, custom CSS, Vite, vanilla JavaScript, and Chart.js. Start with CSS transitions and existing chart animation capabilities; no new animation library is planned.

## 2. Scope and boundaries

Redesign login, registration, feedback creation, submission results, personal feedback history, dashboard, account management, profile settings, shared navigation, alerts, dialogs, and pagination.

Preserve routes, request field names, CSRF protection, validation, redirects, authorization, privacy rules, CCIS scope, moderation, duplicate/rate limits, sentiment analysis, rankings, and exports. No database migration, authentication rewrite, AI-provider change, queue introduction, or new product feature is part of this redesign.

Preserve unrelated work. At inspection, `config/feedback.php`, `tmp/`, and a Word lock file under `output/chapters-1-3-revised/` already had local changes/untracked content. Recheck status before implementation and do not include these in redesign changes.

## 3. Shared design system

### Visual rules

- Retain indigo as the main brand accent. Use neutral backgrounds and surfaces; reserve positive/neutral/negative colors for meaningful statuses.
- Retain Inter for body text and analytics. Explore a rounded heading treatment in mockups, using an appropriately licensed font only if selected; keep tables and chart labels highly readable.
- Define reusable tokens for colors, spacing, typography, radii, borders, focus, and motion. Use a spacing scale based on 4/8 px increments, with roughly 12–16 px control radii and 16–24 px card radii, refined in mockups.
- Use consistent SVG icons with accessible labels on icon-only controls. Navigation keeps visible text labels on desktop.
- Borders and restrained shadows distinguish surfaces. Avoid decorative gradients, continuously moving backgrounds, and unnecessary nested cards.
- Use consistent primary, secondary, destructive, loading, disabled, and focus states across pages.
- Primary buttons have a shallow darker bottom edge and a short press movement that feels tactile without shifting surrounding layout. Use rounded outline icons and occasional soft accent backgrounds.
- Copy is warm, concise, and helpful: welcome users, explain how to fix errors, and thank them for contributing. Keep rejection, privacy, and account-deletion messages factual and respectful.
- Give student forms and account access more personality; keep dense dashboard data calm. Use gentle visual acknowledgment after confirmed actions rather than rewards for feedback volume or sentiment.
- Maintain persistent theme preference and readable charts in both themes. Verify chart colors update when the theme changes.

### Responsive and accessible behavior

- Verify at approximately 360, 390, 768, 1024, and 1440 px widths, and with browser zoom.
- Mobile: single-column forms/cards, accessible drawer navigation, wrapping filter controls, and tables with intentional horizontal scrolling or a readable compact presentation.
- No page-level horizontal overflow. Do not hide essential columns or actions simply to fit mobile.
- Visible keyboard focus, associated field labels/errors, adequate contrast, status labels alongside colors, and appropriately sized controls.
- Dialogs manage focus, support Escape, and return focus to the trigger. Loading changes use accessible busy/status announcements without repeatedly announcing every character count.
- Honor `prefers-reduced-motion` in CSS, JavaScript scrolling, and chart configuration. Content must remain visible if animation or JavaScript fails.

## 4. Screen specifications

### Login — `/login`

- Compact centered form, ResBack branding, welcome heading, short purpose statement, and theme toggle.
- Keep email/password, autocomplete, password visibility control, and Create Account link.
- Show field errors and account/access errors clearly. Preserve email after validation; never repopulate passwords.
- Pending state appears only after a valid submission. Restore controls when browser back/forward caching restores the page.
- No Forgot Password link unless a working recovery route is separately implemented.

### Create Account — `/register`

- Match login styling with room for nickname, email, password, and confirmation.
- Explain nickname and password rules using actual server validation. Preserve Unicode nickname support and uniqueness behavior.
- Preserve nickname/email after errors; keep passwords empty. Keep accessible password visibility controls.
- Registration remains student-only and retains the current successful redirect to feedback creation. Add no role selector, survey, or verification step.

### Submit Feedback — `/feedback/create`

- Buffer-inspired focused layout: heading, concise guidance, privacy explanation, fixed CCIS context, textarea, character count, and primary submit action.
- Preserve the hidden category ID, 2,000-character limit, existing confirmation dialog, and moderation reminder.
- Keep history/profile navigation available. Privacy wording explains that feedback is linked privately to the account and identity is hidden from faculty dashboards/exports; do not claim absolute anonymity.
- On Submit, open the confirmation dialog. Review returns to the form without losing text; Confirm & Submit enters the pending state and prevents repeated clicks.
- Preserve existing synchronous POST and analysis behavior. Use truthful indeterminate wording such as “Submitting feedback…”; no invented percentage, timed stages, or premature success.
- Preserve text for validation failures, including duplicate and rate-limit messages. Network interruption must never be presented as confirmed success; avoid automatic retries that could repeat a saved submission.

### Feedback result and legacy thank-you screen

- Update `feedback/result.blade.php`, which is currently returned directly by submission, with matching typography, status styling, and actions.
- Distinguish accepted/analyzed, rejected, and saved with unavailable analysis using actual persisted status/result data.
- Show a short success/checkmark entrance only for accepted submissions. Rejected feedback gets a clear explanation without success celebration.
- Keep sentiment/confidence, language/confidence, keywords, feedback content, Submit Another Feedback, and View My Feedback where applicable.
- Bring `/feedback/thankyou` into visual consistency without inventing session data or adding a new result route.

### Personal history — `/feedback/history`

- Clear heading and Submit New Feedback action; consistent status badges and readable content.
- Preserve current user-only records, analysis fields, empty state, pagination, and privacy notice.
- Handle long text, missing analysis, rejected entries, and mobile presentation without truncating access to the actual feedback.

### Dashboard — `/dashboard`

- Quicken-inspired shell with labeled sidebar, page heading, theme toggle, filters, and existing export actions.
- Proposed content order: filter context; summary statistics; sentiment distribution and trend; ranked critical concerns with examples; language summaries; feedback table and pagination.
- Keep actual metrics, existing ranking formula, date/language filters, filtered exports, and role-dependent actions. Add no fabricated KPIs or unsupported interactions.
- Use available width for analytics; the reference's tall Accounts panel is not required for ResBack.
- Preserve partial feedback-table pagination and its full-navigation fallback. Filtering stays consistent with the current GET behavior unless a later scoped change is justified.
- Loading affects the content actually being updated. Avoid animations that imply all dashboard metrics changed when only table pagination changed.
- Preserve safe chart sizing, chart labels, empty states, filter errors, and readability in both themes.

### Account management — `/dashboard/accounts`

- Reuse dashboard shell, search/filter styling, readable table, pagination, badges, and action controls.
- Preserve Admin/Super Admin permissions and protected-account behavior in both UI and backend.
- Clearly distinguish role changes, activation/deactivation, and deletion. Keep confirmation for destructive actions and prevent repeated submissions.
- Handle empty search results, validation, and long nicknames without exposing private account fields.

### Profile — `/profile`

- Use the correct student or staff shell. Match form controls and save/error messaging across roles.
- Preserve nickname editing, photo upload/removal, validation, and role-dependent layout.
- Keep file inputs accessible and provide appropriate loading feedback during save.

## 5. Motion specification

- Page entrance: opacity plus 6–10 px upward movement, approximately 250–350 ms, once per page load. A small settling motion may be used for the form heading or confirmation icon. Do not delay access to controls.
- Dashboard groups: optional 40–60 ms stagger with a short total sequence; avoid animating every table row.
- Buttons/inputs/navigation: color, border, and background transitions around 150–200 ms. Primary buttons depress by roughly 2–3 px on activation with a corresponding bottom-edge change; release returns smoothly. Keyboard activation receives equivalent feedback. Static informational cards stay steady.
- Dialog/drawer: approximately 180–250 ms entrance and backdrop fade. Preserve native dialog focus/keyboard behavior.
- Charts: brief initial reveal and data updates, approximately 300–500 ms; no continuous animation or count-up effects that obscure actual totals.
- Partial table updates: maintain layout and existing data during loading, then a short fade after replacement. Announce loading/completion appropriately.
- Result confirmation: a brief checkmark draw/pop with a small settling motion after a confirmed accepted response. Pair with warm wording such as “Thanks for sharing your feedback.” Errors appear gently, explain recovery, and remain until resolved or intentionally dismissed; avoid shaking inputs or celebrating rejected submissions.
- A short emphasis on the selected navigation item or confirmed profile save may provide friendly acknowledgment. Do not loop bounces, animate text while typing, or insert celebration delays before next actions become available.
- Reduced motion: remove translation, stagger, drawing effects, and smooth scrolling; stop nonessential chart animation. Keep understandable static state changes.

Durations are proposed design targets to validate in the mockups, not added waits or artificial loading delays.

## 6. Implementation sequence and completion gates

### Phase 1 — Mockups and baseline

Capture the existing screens using local test accounts/data and record working behavior before editing. Produce separate desktop/mobile mockups for Login, Create Account, Submit Feedback, and Dashboard, with light/dark examples and key states. Include a short interactive motion preview showing the tactile button press, field focus, confirmation dialog, and accepted-result acknowledgment. Mockups use clearly labeled sample data and remain outside production UI.

Original gate: user reviews concrete mockups before production UI edits. Updated by the user's request to start building: implement the agreed direction and provide a working local preview for review.

### Phase 2 — Shared foundation

Implement design tokens, common controls, icons, theme styling, navigation, alerts, dialogs, and reduced-motion behavior. Refactor shared styles carefully rather than adding another conflicting layer of overrides.

Gate: all existing screens remain usable while the common components change; no theme flash, broken navigation, or inaccessible dialog.

### Phase 3 — Account access

Implement Login and Create Account, preserving actual field/validation/redirect behavior and adding consistent pending/error states.

Gate: sign-in, failed sign-in, registration, duplicate nickname/email validation, password toggles, and mobile layouts verified.

### Phase 4 — Student feedback journey

Implement creation, confirmation dialog, results/thank-you, and history using the agreed visual system.

Gate: normal submission, rejected content, validation, duplicate/rate limit, unavailable analysis, history privacy, and pagination verified with controlled test fixtures/provider mocks.

### Phase 5 — Analytics dashboard

Implement shell, filters, summary cards, charts, critical concerns, language summaries, table, and scoped loading/motion.

Gate: metrics and filters match existing behavior; pagination, exports, empty states, role visibility, and theme-responsive charts verified.

### Phase 6 — Accounts and profile

Apply the shared style to account administration and profile settings; cover role restrictions, destructive confirmation, long content, and photo validation.

Gate: Admin/Super Admin restrictions and student/staff profile flows verified.

### Phase 7 — Final verification and handoff

Run the existing test suite, production asset build, Blade compilation, and diff checks. Complete browser QA on the agreed viewports, themes, keyboard navigation, reduced motion, slow responses, and browser back behavior. Resolve failures caused by the redesign and report any existing unrelated failure separately.

Gate: acceptance checklist is satisfied; provide screenshots and a focused change summary. Commit/push only when explicitly requested.

## 7. Expected code touchpoints

- `resources/css/app.css`: tokens, shared components, responsive styles, themes, and motion.
- `resources/js/app.js`: shared interaction/pending states, theme-aware chart handling, reduced-motion scrolling, and existing pagination integration.
- `resources/views/layouts/{auth,guest,app}.blade.php`: consistent shells and navigation.
- `resources/views/components/` and `resources/views/partials/`: reusable icons, controls, and state presentation where useful.
- `resources/views/auth/{login,register}.blade.php`.
- `resources/views/feedback/{create,result,thankyou,history}.blade.php`.
- `resources/views/dashboard/index.blade.php`, `dashboard/partials/feedback-table.blade.php`, and `dashboard/accounts/index.blade.php`.
- `resources/views/profile/edit.blade.php` and pagination templates.

Controllers, routes, services, and schema stay unchanged unless a demonstrated frontend integration issue requires a separately explained narrow fix. PDF report layout is outside this web redesign.

## 8. Verification and acceptance checklist

- [ ] All four initial screen mockups reviewed before production edits.
- [ ] Consistent Quicken/Buffer-inspired layouts with Duolingo-inspired friendly shapes, wording, and subtle motion, retaining ResBack branding throughout the scoped pages.
- [ ] Tactile buttons and success acknowledgment feel responsive without delaying actions; dashboard charts/tables remain steady and readable.
- [ ] Light/dark themes work, including chart text, tooltips, filters, dialogs, and tables.
- [ ] No lost fields, broken routes, exposed identity, weakened permissions, or altered analytics/export results.
- [ ] Validations preserve permitted form values; passwords remain empty after validation.
- [ ] Feedback confirmation remains; pending state prevents repeated clicks; no fake progress or premature success.
- [ ] Accepted, rejected, and saved-with-analysis-failure outcomes are clearly distinguished.
- [ ] Mobile layouts, long feedback/nicknames, and empty states remain readable.
- [ ] Keyboard navigation, focus, dialog behavior, contrast, zoom, and reduced motion pass browser review.
- [ ] Existing automated tests pass: authentication, feedback protection/results/history, dashboard/ranking, exports/reports, account management, and profile settings.
- [ ] `php artisan test`, `npm run build`, `php artisan view:cache`, and `git diff --check` pass; clear generated view cache after the compilation check if appropriate for local development.
- [ ] Add targeted regression tests only for new behavioral changes or demonstrated gaps; do not write tests that merely assert decorative class names.
- [ ] Final diff preserves unrelated local changes. UI implementation and delivery status are reported honestly.

## 9. Next action

Review the working local implementation with the user and refine visual details. The initial pass consolidates shared CSS, applies the friendly indigo style across the scoped web screens, adds tactile controls and reduced motion, improves mobile drawer focus behavior, preserves feedback confirmation, and makes charts respond to theme changes. PDF reports and backend behavior remain outside this UI pass.
