# Private delivery thumbnail processing

Private original uploads remain in the local non-public disk. Preview posters are generated separately under `private-thumbnails/` and are served only through participant-authorized routes.

This is an **optional** command-based processor, not an active queue worker or automated scheduler. Install FFmpeg at `/usr/bin/ffmpeg` on the processing host, run migrations and invoke:

```bash
php artisan migrate
php artisan vibsies:process-previews --limit=20
```

You can schedule that command after testing the production hosting environment. Missing FFmpeg does not block video uploads; pending items remain pending. Failed conversions are marked failed and require operational review before retry. The processor creates poster JPEGs, **not** resized/transcoded video streams or watermark-protected previews. Thumbnail access remains restricted to booking participants.

Security and reliability validation still required: malware scanning, file probing and size limits, race/concurrency test, media worker isolation, retention policy, real-browser range playback and PostgreSQL migration testing.
