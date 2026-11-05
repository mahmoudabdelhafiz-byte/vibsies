<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AdLedgerBatch extends Model {
    use HasUuids;
    protected $guarded=['id','created_at','updated_at'];
    protected function casts():array{return ['gross_minor'=>'integer','publisher_share_percent'=>'integer'];}
    public function entries(){return $this->hasMany(AdLedgerEntry::class);}
    public function offer(){return $this->belongsTo(AdCampaignOffer::class,'ad_campaign_offer_id');}
}