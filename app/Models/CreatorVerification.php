<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class CreatorVerification extends Model {
 use HasUuids;
 protected $fillable=['creator_profile_id','type','status','reference_hash','reviewed_by','reviewed_at','expires_at'];
 protected function casts():array{return ['reviewed_at'=>'datetime','expires_at'=>'datetime'];}
 public function creator(){return $this->belongsTo(CreatorProfile::class,'creator_profile_id');}
}