<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AdCampaignOffer extends Model {
    use HasUuids;
    protected $fillable=['ad_campaign_id','publisher_ad_package_id','publisher_user_id','agreed_price_minor','currency','expires_at'];
    protected function casts(): array {
        return ['agreed_price_minor'=>'integer','accepted_at'=>'datetime','submitted_at'=>'datetime','reviewed_at'=>'datetime','expires_at'=>'datetime','cancelled_at'=>'datetime','resolved_at'=>'datetime'];
    }
    public function campaign() { return $this->belongsTo(AdCampaign::class,'ad_campaign_id'); }
    public function package() { return $this->belongsTo(PublisherAdPackage::class,'publisher_ad_package_id'); }
    public function campaignReview(){return $this->hasOne(PublisherCampaignReview::class,'ad_campaign_offer_id');}
    public function performanceReport(){return $this->hasOne(PublisherCampaignPerformanceReport::class,'ad_campaign_offer_id');}
    public function events(){return $this->hasMany(AdOfferEvent::class,'ad_campaign_offer_id');}
    public function publisher() { return $this->belongsTo(User::class,'publisher_user_id'); }
}