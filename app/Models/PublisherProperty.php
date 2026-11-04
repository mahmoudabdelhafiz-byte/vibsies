<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PublisherProperty extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'platform', 'property_type', 'display_name', 'profile_url',
        'category', 'country_code', 'city', 'reported_followers', 'description',
        'ownership_evidence_url', 'ownership_evidence_note', 'verification_token',
    ];

    protected function casts(): array
    {
        return ['reported_followers' => 'integer', 'verified_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function adPackages() { return $this->hasMany(PublisherAdPackage::class); }

    public function metricSnapshots() { return $this->hasMany(PublisherMetricSnapshot::class); }

    public function rewardEntitlement(){return $this->hasOne(PublisherRewardEntitlement::class);}

    public function promotions()
    {
        return $this->hasMany(PublisherPromotion::class);
    }
}
