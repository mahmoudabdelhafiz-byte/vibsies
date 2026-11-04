<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PublisherVerificationEvent extends Model
{
    use HasUuids;
    public $timestamps = false;
    const UPDATED_AT = null;

    protected $fillable = ['publisher_property_id','actor_id','action','decision','note'];
    protected function casts(): array { return ['created_at'=>'datetime']; }
    public function property() { return $this->belongsTo(PublisherProperty::class,'publisher_property_id'); }
}
