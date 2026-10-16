<?php
use Illuminate\Support\Facades\Artisan;
Artisan::command('vibsies:status', function (): void { $this->info('Vibsies Laravel marketplace foundation'); });

use Illuminate\Support\Facades\Schedule;
// Only run when explicitly enabled on a host configured with private media storage and FFmpeg.
if (filter_var(env('VIBSIES_AUTO_PREVIEWS',false),FILTER_VALIDATE_BOOLEAN)) {
 Schedule::command('vibsies:process-previews --limit=20')->everyFiveMinutes()->withoutOverlapping(15);
}
