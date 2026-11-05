<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AdOfferEvent extends Model {
    use HasUuids;
    public $timestamps=false;
    const UPDATED_AT=null;
    protected $fillable=['ad_campaign_offer_id','actor_id','event_type','from_status','to_status','note'];
    protected function casts():array{return ['created_at'=>'datetime'];}
    public function offer(){return $this->belongsTo(AdCampaignOffer::class,'ad_campaign_offer_id');}
}