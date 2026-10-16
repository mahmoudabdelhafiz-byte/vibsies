<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable {
 use Notifiable;
 protected $fillable=['name','email','password','locale'];
 protected $hidden=['password','remember_token'];
 protected function casts(): array { return ['password'=>'hashed','email_verified_at'=>'datetime']; }
 public function creatorProfile(){return $this->hasOne(CreatorProfile::class);}
 public function organizations(){return $this->belongsToMany(Organization::class)->withPivot('role')->withTimestamps();}
}
