<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Dispute extends Model {
 use HasUuids;
 protected $fillable=['booking_id','raised_by','reason','status'];
 public function booking(){return $this->belongsTo(Booking::class);}
}