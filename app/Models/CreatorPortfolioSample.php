<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class CreatorPortfolioSample extends Model {
 use HasUuids;
 protected $fillable=['creator_profile_id','title','description','video_url','moderation_status'];
 public function creator(){return $this->belongsTo(CreatorProfile::class,'creator_profile_id');}
}
