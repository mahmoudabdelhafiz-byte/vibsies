<?php
namespace App\Console\Commands;
use App\Models\Delivery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
class ProcessDeliveryPreviews extends Command {
 protected $signature='vibsies:process-previews {--limit=20 : Maximum deliveries per run}';
 protected $description='Generate private thumbnails for pending video deliveries with FFmpeg';
 public function handle():int {
  $limit=max(1,min(100,(int)$this->option('limit')));
  if(!is_executable('/usr/bin/ffmpeg')){
   $this->error('FFmpeg unavailable at /usr/bin/ffmpeg; pending deliveries remain unchanged.');
   return self::FAILURE;
  }
  $count=0;
  foreach(Delivery::where('processing_status','pending')->where('storage_disk','local')->whereNotNull('storage_path')->orderBy('created_at')->limit($limit)->get() as $delivery){
   // Atomic transition prevents concurrent command runs from processing the same entry.
   if(Delivery::whereKey($delivery->id)->where('processing_status','pending')->update(['processing_status'=>'processing'])!==1)continue;
   $thumbnail='private-thumbnails/'.$delivery->booking_id.'/'.$delivery->id.'.jpg';
   try {
    $disk=Storage::disk('local');
    if(!$disk->exists($delivery->storage_path))throw new \RuntimeException('Original video missing');
    $disk->makeDirectory(dirname($thumbnail));
    $process=new Process(['/usr/bin/ffmpeg','-hide_banner','-nostdin','-loglevel','error','-y','-ss','00:00:01','-i',$disk->path($delivery->storage_path),'-frames:v','1','-vf','scale=640:-2','-q:v','4',$disk->path($thumbnail)]);
    $process->setTimeout(120);
    $process->run();
    if(!$process->isSuccessful() || !$disk->exists($thumbnail))throw new \RuntimeException('Thumbnail generation failed');
    $delivery->update(['thumbnail_path'=>$thumbnail,'processing_status'=>'ready','processing_error'=>null,'processed_at'=>now()]);
    $count++;
   }catch(\Throwable $e){
    Storage::disk('local')->delete($thumbnail);
    $delivery->update(['processing_status'=>'failed','processing_error'=>mb_substr($e->getMessage(),0,300)]);
   }
  }
  $this->info("Generated {$count} private video thumbnails.");
  return self::SUCCESS;
 }
}
