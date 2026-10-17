<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Booking extends Model {
 use HasUuids;
 protected $fillable=['customer_id','creator_profile_id','creator_package_id','organization_id','brief','price_minor','fee_minor','currency','status'];
 public function creator(){return $this->belongsTo(CreatorProfile::class,'creator_profile_id');}
 public function customer(){return $this->belongsTo(User::class,'customer_id');}
 public function agreement(){return $this->hasOne(Agreement::class);}
}
