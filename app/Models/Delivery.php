<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Delivery extends Model {
 use HasUuids;
 protected $fillable=['booking_id','creator_user_id','version','media_url','note','status'];
 public function booking(){return $this->belongsTo(Booking::class);}
}