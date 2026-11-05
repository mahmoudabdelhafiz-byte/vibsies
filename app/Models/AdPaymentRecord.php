<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AdPaymentRecord extends Model {
    use HasUuids;
    protected $fillable=[];
    protected function casts():array{return ['amount_minor'=>'integer','verified_at'=>'datetime'];}
    public function offer(){return $this->belongsTo(AdCampaignOffer::class,'ad_campaign_offer_id');}
}