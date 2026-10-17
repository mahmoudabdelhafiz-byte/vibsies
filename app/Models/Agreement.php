<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Agreement extends Model {
 use HasUuids;
 protected $fillable=['booking_id','terms','terms_sha256','customer_accepted_at','creator_accepted_at'];
 protected function casts():array{return ['terms'=>'array','customer_accepted_at'=>'datetime','creator_accepted_at'=>'datetime'];}
}
