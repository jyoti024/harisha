# Eventify – Event Ticket Booking (PHP + SQLite)

The database is built in: a single file at `data/eventify.sqlite`, created
automatically (with 8 sample events) the first time the site opens.
No MySQL, no import, no database settings.

## Run locally (XAMPP / WAMP)
1. Copy this folder to `htdocs/`.
2. Start Apache and open http://localhost/event_ticket_booking/
   (needs the `sqlite3` PHP extension, which is on by default).

## Deploy on Render
1. Push this folder to GitHub (the `Dockerfile` must be at the repo root).
2. Render -> New -> Web Service -> pick the repo.
3. Language: Docker. Root Directory: blank. Start Command: blank. Instance: Free.
4. Create Web Service. No environment variables are needed.

Note: on Render's free plan the disk is reset on every restart/redeploy, so
users and bookings are cleared and the sample events are re-created. For
permanent data, add a Render Disk (paid), mount it at `/data`, and set the
environment variable `DB_PATH=/data/eventify.sqlite`.

Optional variables: `SEED_SAMPLE_DATA=false` (start with no sample events).
