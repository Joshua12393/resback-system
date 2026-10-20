# Deploying ResBack to Render + Supabase (defense demo)

Target: online copy for the facilitator and panel, October 27–31, 2026. The XAMPP laptop copy stays as the backup.

What the repo already provides: `Dockerfile`, `docker/start.sh` (migrates, seeds categories and the first Super Admin, caches config, starts Apache), `render.yaml`, Postgres-safe migrations, and Supabase Storage support for profile photos (`PROFILE_PHOTO_DISK=s3`).

## 1. Supabase (database + photo storage)

1. Create a project at supabase.com. Pick the region closest to your users (e.g. Southeast Asia / Singapore). Save the database password somewhere safe.
2. **Database connection:** Project → **Connect** → choose **Session pooler** (Render does not support the IPv6 direct connection). Note:
   - host, e.g. `aws-0-ap-southeast-1.pooler.supabase.com`
   - port `5432`
   - user, e.g. `postgres.<project-ref>`
   - database `postgres`
3. **Storage bucket:** Storage → New bucket → name `resback`, set it to **Public**.
4. **S3 keys:** Storage → Settings → S3 Connection → enable it, then **New access key**. Note:
   - endpoint, e.g. `https://<project-ref>.supabase.co/storage/v1/s3`
   - region, e.g. `ap-southeast-1`
   - access key ID and secret
5. Public photo URL base: `https://<project-ref>.supabase.co/storage/v1/object/public/resback`

## 2. Render (web app)

1. Push this repository to GitHub (`main`).
2. render.com → **New → Blueprint** → select the repository. Render reads `render.yaml`.
3. Fill the variables marked secret:

| Variable | Value |
|---|---|
| `APP_KEY` | Run `php artisan key:generate --show` locally and paste the output. Never change it after launch. |
| `APP_URL` | `https://<your-service>.onrender.com` (update after the first deploy if the name differs) |
| `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD` | From Supabase step 2 |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` | From Supabase step 4 |
| `AWS_DEFAULT_REGION`, `AWS_ENDPOINT` | From Supabase step 4 |
| `AWS_URL` | Public URL base from Supabase step 5 |
| `GEMINI_API_KEY` | Same key that works locally |
| `RESBACK_ADMIN_EMAIL`, `RESBACK_ADMIN_PASSWORD` | Login for the first Super Admin. Use a strong password. It is created once and never overwritten. |

4. Deploy. The first build takes several minutes. Check the logs for `Super Admin account created.`
5. Open `https://<your-service>.onrender.com/up`. It should return a healthy status.

## 3. Smoke test on the live site

- [ ] Super Admin login → dashboard loads
- [ ] Register a student account → submit feedback → sentiment result appears (Gemini works)
- [ ] Profanity and duplicate/rate-limit blocks show their messages
- [ ] Concern ranking, PDF export, and Excel export download
- [ ] Upload a profile photo → it still shows after **Manual Deploy → Restart**
- [ ] Admin deactivates an account → that account can no longer log in
- [ ] Five wrong passwords → "Too many attempts" message

## 4. Demo-day notes

- Render Free sleeps after about 15 minutes idle; the first visit can take up to a minute. Open the site 5–10 minutes before presenting.
- Supabase Free pauses projects after a period of inactivity. Log in to the dashboard the day before and make sure the project is active.
- Tag the commit you demo: `git tag defense-demo && git push origin defense-demo`.
- Back up the online database the day before (Supabase → Database → Backups, or `pg_dump` with the session-pooler details).
- Fallback: the XAMPP laptop copy with its own MySQL data, plus a screen recording of the full flow.
- Email stays on `MAIL_MAILER=log`; no real emails are sent.
