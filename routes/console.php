<?php
use Illuminate\Support\Facades\Artisan;
Artisan::command('vibsies:status', function (): void { $this->info('Vibsies Laravel marketplace foundation'); });

use Illuminate\Support\Facades\Schedule;
// Only run when explicitly enabled on a host configured with private media storage and FFmpeg.
if (filter_var(env('VIBSIES_AUTO_PREVIEWS',false),FILTER_VALIDATE_BOOLEAN)) {
 Schedule::command('vibsies:process-previews --limit=20')->everyFiveMinutes()->withoutOverlapping(15);
}

// Expire time-limited verification checks; public view also filters expired timestamps.
Schedule::command('vibsies:expire-verifications')->daily()->withoutOverlapping(15);

// In-app notices at 30, 7 and 1 days before time-limited verification expires.
Schedule::command('vibsies:remind-verification-renewals')->daily()->withoutOverlapping(15);
