<?php
namespace App\Console\Commands;
use App\Models\Delivery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
class AuditPrivateMedia extends Command {
 protected $signature='vibsies:audit-media {--limit=500 : Maximum deliveries to inspect}';
 protected $description='Read-only audit of private delivery media and thumbnails';
 public function handle():int {
  $limit=max(1,min(5000,(int)$this->option('limit')));
  $missingOriginal=0;$missingThumbnail=0;$unsafePath=0;$inspected=0;
  $disk=Storage::disk('local');
  Delivery::where('storage_disk','local')->orderBy('id')->limit($limit)->get()
   ->each(function(Delivery $delivery)use($disk,&$missingOriginal,&$missingThumbnail,&$unsafePath,&$inspected){
    $inspected++;
    $prefix='private-deliveries/'.$delivery->booking_id.'/';
    $thumbPrefix='private-thumbnails/'.$delivery->booking_id.'/';
    $validOriginal=is_string($delivery->storage_path) && str_starts_with($delivery->storage_path,$prefix) && !str_contains($delivery->storage_path,'..');
    $validThumbnail=!$delivery->thumbnail_path || (str_starts_with($delivery->thumbnail_path,$thumbPrefix) && !str_contains($delivery->thumbnail_path,'..'));
    if(!$validOriginal || !$validThumbnail){$unsafePath++;return;}
    if(!$disk->exists($delivery->storage_path))$missingOriginal++;
    if($delivery->thumbnail_path && !$disk->exists($delivery->thumbnail_path))$missingThumbnail++;
   });
  $this->table(['Metric','Count'],[
   ['Inspected deliveries',$inspected],
   ['Missing originals',$missingOriginal],
   ['Missing thumbnails',$missingThumbnail],
   ['Unsafe storage paths',$unsafePath]
  ]);
  $this->info('Read-only audit: no media deleted or changed.');
  return ($missingOriginal || $missingThumbnail || $unsafePath)?self::FAILURE:self::SUCCESS;
 }
}
