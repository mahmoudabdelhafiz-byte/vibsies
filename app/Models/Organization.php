<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Organization extends Model {
 use HasUuids;
 protected $fillable=['name','country_code','owner_id'];
 public function members(){return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();}
}
