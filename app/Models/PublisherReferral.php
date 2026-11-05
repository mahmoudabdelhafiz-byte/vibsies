<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PublisherReferral extends Model {
    use HasUuids;
    protected $fillable=['publisher_promotion_id','referred_user_id','status','registered_at'];
    protected function casts():array{return ['registered_at'=>'datetime','qualified_at'=>'datetime','reviewed_at'=>'datetime'];}
    public function reviewEvents(){return $this->hasMany(PublisherReferralReviewEvent::class);}
    public function promotion(){return $this->belongsTo(PublisherPromotion::class,'publisher_promotion_id');}
    public function referredUser(){return $this->belongsTo(User::class,'referred_user_id');}
}