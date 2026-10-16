# Private delivery thumbnail processing

Private original uploads remain in the local non-public disk. Preview posters are generated separately under `private-thumbnails/` and are served only through participant-authorized routes.

This is an **optional** command-based processor. Scheduling can be enabled per host, but is disabled by default. Install FFmpeg at `/usr/bin/ffmpeg` on the processing host, run migrations and invoke:

```bash
php artisan migrate
php artisan vibsies:process-previews --limit=20
```

To opt in, set `VIBSIES_AUTO_PREVIEWS=true` on a host with FFmpeg and private media storage, then run Laravel's scheduler every minute (`php artisan schedule:run` from cron or an equivalent scheduler service). The job is scheduled every five minutes with an overlap lock. Do not enable it on multiple hosts unless they share the cache lock backend and media storage. For an explicit bounded retry of failed items, run `php artisan vibsies:process-previews --limit=20 --retry-failed`. Failed items are never retried automatically. Missing FFmpeg does not block video uploads; pending items remain pending. Failed conversions are marked failed and require operational review before retry. The processor creates poster JPEGs, **not** resized/transcoded video streams or watermark-protected previews. Thumbnail access remains restricted to booking participants.

Security and reliability validation still required: malware scanning, file probing and size limits, race/concurrency test, media worker isolation, retention policy, real-browser range playback and PostgreSQL migration testing.
