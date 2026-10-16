<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Delivery extends Model {
 use HasUuids;
 protected $fillable=['booking_id','creator_user_id','version','media_url','storage_disk','storage_path','original_filename','mime_type','file_size','sha256','note','status'];
 public function booking(){return $this->belongsTo(Booking::class);}
}