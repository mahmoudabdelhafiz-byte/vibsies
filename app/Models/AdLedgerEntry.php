<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AdLedgerEntry extends Model {
    use HasUuids;
    protected $guarded=['id','created_at','updated_at'];
    protected function casts():array{return ['amount_minor'=>'integer'];}
    public function batch(){return $this->belongsTo(AdLedgerBatch::class,'ad_ledger_batch_id');}
}