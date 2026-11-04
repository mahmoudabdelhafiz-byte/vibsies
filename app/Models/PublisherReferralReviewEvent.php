<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PublisherReferralReviewEvent extends Model
{
    use HasUuids;
    public $timestamps = false;
    const UPDATED_AT = null;

    protected $fillable = ['publisher_referral_id','actor_id','decision','note'];
    protected function casts(): array { return ['created_at' => 'datetime']; }
    public function referral() { return $this->belongsTo(PublisherReferral::class,'publisher_referral_id'); }
}
