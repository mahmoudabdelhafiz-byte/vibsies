<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PublisherAdPackage extends Model {
    use HasUuids;
    protected $fillable = ['publisher_property_id','title','placement_type','description','price_minor','currency','duration_hours','delivery_days'];
    protected function casts(): array { return ['price_minor'=>'integer','is_active'=>'boolean']; }
    public function property() { return $this->belongsTo(PublisherProperty::class,'publisher_property_id'); }
}