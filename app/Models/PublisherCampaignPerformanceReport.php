<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PublisherCampaignPerformanceReport extends Model
{
    use HasUuids;
    protected $fillable=['ad_campaign_offer_id','publisher_property_id','evidence_source','views','reach','clicks','interactions','evidence_url','measured_on'];
    protected function casts(): array {
        return ['views'=>'integer','reach'=>'integer','clicks'=>'integer','interactions'=>'integer','measured_on'=>'date','verified_at'=>'datetime'];
    }
    public function offer(){return $this->belongsTo(AdCampaignOffer::class,'ad_campaign_offer_id');}
    public function property(){return $this->belongsTo(PublisherProperty::class,'publisher_property_id');}
}