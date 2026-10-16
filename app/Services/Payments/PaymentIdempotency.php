<?php
namespace App\Services\Payments;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

/**
 * Reservation-only idempotency primitive. Does not call payment providers.
 * The unique (operation, request_key) index arbitrates concurrent callers.
 */
final class PaymentIdempotency {
 public function reserve(string $operation,string $key,string $requestBody):bool {
  if(app()->environment('production'))throw new LogicException('Payment operations are disabled in production.');
  if(!preg_match('/^[a-z][a-z0-9_-]{1,63}$/',$operation)
    || strlen($key)<8 || strlen($key)>191 || preg_match('/[\x00-\x1F\x7F]/',$key))
   throw new InvalidArgumentException('Invalid idempotency scope or key.');
  if(strlen($requestBody)>65536)throw new InvalidArgumentException('Request body too large.');
  $hash=hash('sha256',$requestBody);
  $inserted=DB::table('payment_idempotency_keys')->insertOrIgnore([
   'id'=>(string)Str::uuid(),'operation'=>$operation,'request_key'=>$key,
   'request_sha256'=>$hash,'status'=>'reserved','created_at'=>now(),'updated_at'=>now()
  ]);
  if($inserted===1)return true;
  $existing=DB::table('payment_idempotency_keys')->where('operation',$operation)->where('request_key',$key)->first();
  if(!$existing || !hash_equals($existing->request_sha256,$hash))
   throw new LogicException('Idempotency key reused with a different request.');
  return false;
 }
}
