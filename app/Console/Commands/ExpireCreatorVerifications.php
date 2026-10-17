<?php
namespace App\Console\Commands;
use App\Models\CreatorVerification;
use Illuminate\Console\Command;
class ExpireCreatorVerifications extends Command {
 protected $signature='vibsies:expire-verifications {--dry-run : Preview without updating records}';
 protected $description='Expire verified creator checks whose expiry timestamp has passed';
 public function handle():int {
  $query=CreatorVerification::query()->where('status','verified')->whereNotNull('expires_at')->where('expires_at','<=',now());
  $count=(clone $query)->count();
  if($this->option('dry-run')){$this->info("Expired checks to update: {$count} (dry run).");return self::SUCCESS;}
  $updated=$query->update(['status'=>'expired','updated_at'=>now()]);
  $this->info("Expired checks updated: {$updated}.");
  return self::SUCCESS;
 }
}
