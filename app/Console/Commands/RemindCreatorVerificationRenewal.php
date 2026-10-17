<?php
namespace App\Console\Commands;
use App\Models\CreatorVerification;
use App\Notifications\CreatorVerificationRenewal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
class RemindCreatorVerificationRenewal extends Command {
 protected $signature='vibsies:remind-verification-renewals {--dry-run : Show eligible checks without delivering notifications}';
 protected $description='Create at most one database renewal notice per verification and milestone';
 public function handle():int {
  $count=0;
  foreach([30,7,1] as $days){
   $date=now()->addDays($days)->toDateString();
   CreatorVerification::with('creator')->where('status','verified')->whereNotNull('reviewed_at')
    ->whereDate('expires_at',$date)->chunkById(100,function($checks)use($days,&$count){
     foreach($checks as $v){
      if($v->creator?->status!=='approved')continue;
      $count++;
      if($this->option('dry-run'))continue;
      $notification=new CreatorVerificationRenewal($v->id,$v->type,$v->expires_at->toDateString());
      // Deterministic PK prevents duplicate notifications across overlapping scheduler runs.
      $id=Uuid::uuid5(Uuid::NAMESPACE_URL,'vibsies:verification-renewal:'.$v->id.':'.$days)->toString();
      DB::table('notifications')->insertOrIgnore([
       'id'=>$id,'type'=>CreatorVerificationRenewal::class,
       'notifiable_type'=>\App\Models\User::class,'notifiable_id'=>$v->creator->user_id,
       'data'=>json_encode($notification->toArray($v->creator->user),JSON_THROW_ON_ERROR),
       'created_at'=>now(),'updated_at'=>now()
      ]);
     }
    });
  }
  $this->info('Eligible verification renewal milestones: '.$count.($this->option('dry-run')?' (dry run)':' (duplicates ignored)'));
  return self::SUCCESS;
 }
}
