<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PublisherCampaignReview extends Model
{
    use HasUuids;
    protected $fillable=['ad_campaign_offer_id','publisher_property_id','advertiser_user_id','overall_rating','communication_rating','timeliness_rating','quality_rating','comment'];
    protected function casts(): array {
        return ['overall_rating'=>'integer','communication_rating'=>'integer','timeliness_rating'=>'integer','quality_rating'=>'integer','reviewed_at'=>'datetime','publisher_responded_at'=>'datetime'];
    }
    public function offer(){return $this->belongsTo(AdCampaignOffer::class,'ad_campaign_offer_id');}
    public function property(){return $this->belongsTo(PublisherProperty::class,'publisher_property_id');}
    public function appeal(){return $this->hasOne(PublisherReviewAppeal::class,'publisher_campaign_review_id');}
    public function advertiser(){return $this->belongsTo(User::class,'advertiser_user_id');}
}