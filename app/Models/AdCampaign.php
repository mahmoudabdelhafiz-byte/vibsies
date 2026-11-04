<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AdCampaign extends Model {
    use HasUuids;
    protected $fillable=['user_id','organization_id','title','brief','objective','target_category','target_country','target_city','placement_type','budget_minor','currency'];
    protected function casts(): array { return ['budget_minor'=>'integer']; }
    public function user() { return $this->belongsTo(User::class); }
    public function organization() { return $this->belongsTo(Organization::class); }
}