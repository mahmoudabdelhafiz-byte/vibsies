<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PublisherPromotion extends Model
{
    use HasUuids;

    protected $fillable = [
        'publisher_property_id', 'referral_code', 'post_url',
        'post_format', 'disclosure_confirmed',
    ];

    protected function casts(): array
    {
        return ['disclosure_confirmed' => 'boolean', 'reviewed_at' => 'datetime'];
    }

    public function referrals() { return $this->hasMany(PublisherReferral::class); }

    public function entitlement() { return $this->hasOne(PublisherRewardEntitlement::class); }

    public function property()
    {
        return $this->belongsTo(PublisherProperty::class, 'publisher_property_id');
    }
}
