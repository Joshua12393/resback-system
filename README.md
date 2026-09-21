# ResBack — CCIS Feedback System

ResBack is a web-based feedback and analytics system developed for students of the College of Computing and Information Sciences (CCIS). It gives students a private channel for submitting feedback and helps faculty and administrators identify recurring concerns through sentiment analysis, multilingual classification, dashboards, and concern ranking.

The project was created for a Software Engineering course and is currently limited to CCIS, the department that granted permission for the initial deployment.

## Project objectives

- Make feedback submission accessible to authenticated CCIS students.
- Protect student identity from faculty-facing dashboards and exported reports.
- Classify feedback as positive, neutral, or negative using AI-assisted sentiment analysis.
- Recognize English, Tagalog, Ilocano, and mixed-language feedback.
- Group reports into concern topics and rank the issues that need attention.
- Give faculty and administrators useful summaries instead of requiring them to manually read every submission.

## How the system works

1. A registered user signs in and submits feedback for CCIS.
2. Submission safeguards check rate limits, duplicates, and configured rejection terms before external analysis.
3. Accepted feedback is sent to Gemma through the Google Gemini API.
4. The response is validated and normalized into sentiment, confidence, keywords, languages, and concern topics.
5. Concern rankings combine negative volume, negative ratio, model confidence, and recency.
6. Faculty and administrators view filtered analytics, feedback trends, language distributions, and leading concerns on the dashboard.
7. Authorized users can generate date-filtered Excel exports and PDF reports.

## Main features

### Student experience

- Account registration and login with a one-hour session lifetime
- Private CCIS feedback submission
- Submission confirmation and malicious-content warning
- Duplicate and rapid-submission protection
- Personal feedback history with sentiment, confidence, language, keywords, and processing status
- Profile settings with name and profile-photo customization
- Light and dark themes

### Sentiment and concern analysis

- Gemma integration through the Google Gemini API
- Positive, neutral, and negative sentiment classification
- Sentiment and language confidence scores
- English, Tagalog, Ilocano, Taglish, Iloclish, Taglocano, three-language, and other-language categories
- Keyword extraction and normalized concern topics
- Critical-concern scoring based on:
  - 40% negative-feedback volume
  - 25% negative ratio
  - 20% average negative confidence
  - 15% recency

### Faculty and administrative dashboard

- Sentiment distribution and positive-versus-negative trend charts
- Ranked critical concerns with supporting feedback examples
- Language classification summaries
- Start-date, end-date, and language filtering
- Paginated feedback table that updates without reloading the complete dashboard
- Fresh `.xlsx` exports based on the selected filters
- Downloadable PDF analytics reports

### Administration and moderation

- Student, faculty, admin, and protected super-admin roles
- Account role assignment, activation, deactivation, and deletion
- Super-admin role protection
- Configurable automatic feedback-rejection dictionary and controlled spelling variants
- Admin and Super Admin feedback deletion with server-side authorization

## Privacy and moderation

Feedback is linked privately to the submitting account so students can review their own history and the system can enforce anti-spam safeguards. Student identity is not displayed in the faculty dashboard or included in feedback exports.

Automatically rejected feedback is stored with a `rejected` status, is not sent to Gemma, and is excluded from faculty analytics and generated reports. Rejection words and their controlled spelling variants can be maintained in [`config/feedback.php`](config/feedback.php). After changing that file in an environment using cached configuration, run:

```bash
php artisan config:clear
```

Dictionary matching is intentionally deterministic rather than broadly fuzzy. This reduces accidental rejection of legitimate Tagalog or Ilocano words that happen to resemble a prohibited term.

## Technology stack

- PHP 8.3+
- Laravel 13
- MySQL or MariaDB for deployment; SQLite is supported by the default development configuration
- Blade, custom CSS, and Vite
- Chart.js for dashboard visualizations
- Google Gemini API with Gemma for feedback analysis
- PhpSpreadsheet for Excel exports
- Dompdf for PDF reports
- PHPUnit for automated testing

## Local installation

### Requirements

- PHP 8.3 or newer with the Laravel-required extensions
- Composer
- Node.js and npm
- MySQL/MariaDB or SQLite
- A Google AI Studio API key with access to the configured Gemma model

### Setup

```bash
git clone https://github.com/Joshua12393/resback-system.git
cd resback-system
composer install
```

Create the environment file and application key:

```bash
cp .env.example .env
php artisan key:generate
```

On Windows PowerShell, use `Copy-Item .env.example .env` instead of `cp` if necessary.

Configure the application, database, and model in `.env`. For a typical XAMPP MySQL installation:

```dotenv
APP_NAME=ResBack
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=resback_db
DB_USERNAME=root
DB_PASSWORD=

GEMINI_API_KEY=your_google_ai_studio_api_key
GEMINI_MODEL=gemma-4-26b-a4b-it
```

Never commit a real API key to Git.

Prepare the database and public profile-photo storage:

```bash
php artisan migrate --seed
php artisan storage:link
```

Install and build frontend dependencies:

```bash
npm install
npm run build
```

Start the development server:

```bash
php artisan serve
```

Open `http://127.0.0.1:8000`. Newly registered accounts receive the `student` role by default. An initial administrator or super administrator must be provisioned before dashboard roles can be managed through the application.

## Development commands

Run the automated test suite:

```bash
php artisan test
```

Run the frontend development server:

```bash
npm run dev
```

Rebuild concern topics and rankings after changing topic-normalization rules:

```bash
php artisan feedback:backfill-concerns --force
```

## User roles

- **Student** — submits feedback and views personal feedback history.
- **Faculty** — views dashboard analytics and generates filtered reports.
- **Admin** — manages ordinary accounts and can delete feedback.
- **Super Admin** — has protected administrative access and can assign or manage the Super Admin role.

## Current scope and limitations

- Feedback categories are currently restricted to CCIS.
- AI classifications support decision-making but should not be treated as proof that a report is factually true.
- The configurable dictionary catches known terms and curated variations; it cannot detect every possible obfuscation.
- Broad abusive terms may appear in a legitimate quoted report, so a future moderation queue with `Needs Review` status is recommended.
- Issue resolution and closure tracking are not yet implemented.

## Project team

- **Team Lead / Full-Stack:** Julian Shaun Viloria
- **Lead Developer:** Ckhiel Joshua Queypo
- **Documentation Lead:** Leah Joy Orenza
- **UI/UX Developer:** Marvin Jess Quina
- **QA Tester:** Adrian Ballesteros

## Additional documentation

The original project description, proposed processing flow, and team information are available in [`documentation/PROJECT_INFO.md`](documentation/PROJECT_INFO.md).
