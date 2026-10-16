<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class CreatorPackage extends Model {
 use HasUuids;
 protected $fillable=['creator_profile_id','title','description','price_minor','currency','turnaround_days','revision_limit','is_active'];
 protected function casts():array{return ['is_active'=>'boolean','price_minor'=>'integer'];}
 public function creator(){return $this->belongsTo(CreatorProfile::class,'creator_profile_id');}
}
