<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PublisherRewardEntitlement extends Model {
    use HasUuids;
    protected $fillable=['publisher_property_id','publisher_promotion_id','publisher_share_percent','placement_limit','placements_used','status','eligible_from','expires_at'];
    protected function casts():array{return ['eligible_from'=>'datetime','expires_at'=>'datetime','publisher_share_percent'=>'integer','placement_limit'=>'integer','placements_used'=>'integer'];}
    public function property(){return $this->belongsTo(PublisherProperty::class,'publisher_property_id');}
    public function promotion(){return $this->belongsTo(PublisherPromotion::class,'publisher_promotion_id');}
}