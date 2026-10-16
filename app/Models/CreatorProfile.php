<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class CreatorProfile extends Model {
 use HasUuids;
 protected $fillable=['user_id','display_name','slug','category','bio','country_code','status'];
 public function user(){return $this->belongsTo(User::class);}
 public function portfolioSamples(){return $this->hasMany(CreatorPortfolioSample::class);}
 public function packages(){return $this->hasMany(CreatorPackage::class);}
}
