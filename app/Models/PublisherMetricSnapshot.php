<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PublisherMetricSnapshot extends Model {
    use HasUuids;
    protected $fillable = ['publisher_property_id','observed_on','content_category','follower_count','views','reach','reactions','comments','shares','source_type','evidence_url'];
    protected function casts(): array { return ['observed_on'=>'date','follower_count'=>'integer','views'=>'integer','reach'=>'integer','reactions'=>'integer','comments'=>'integer','shares'=>'integer']; }
    public function property() { return $this->belongsTo(PublisherProperty::class,'publisher_property_id'); }
}