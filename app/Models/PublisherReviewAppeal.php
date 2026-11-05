<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PublisherReviewAppeal extends Model {
 use HasUuids;
 protected $fillable=['publisher_campaign_review_id','publisher_user_id','reason'];
 protected function casts():array{return ['resolved_at'=>'datetime'];}
 public function review(){return $this->belongsTo(PublisherCampaignReview::class,'publisher_campaign_review_id');}
}
