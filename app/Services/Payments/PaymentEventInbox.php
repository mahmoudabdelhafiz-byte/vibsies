<?php
namespace App\Services\Payments;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
final class PaymentEventInbox {
 /**
  * Records only event identifiers and hashes; no untrusted payload execution,
  * money movement or state transitions. This is not a public webhook endpoint.
  */
 public function record(string $provider,string $eventId,string $payload):bool {
  if(app()->environment('production'))throw new LogicException('Payment inbox disabled in production until provider verification is implemented.');
  if(!preg_match('/^[a-z0-9_-]{2,64}$/',$provider) || $eventId==='' || strlen($eventId)>191)throw new \InvalidArgumentException('Invalid event identifier');
  $hash=hash('sha256',$payload);
  $existing=DB::table('payment_webhook_events')->where('provider',$provider)->where('external_event_id',$eventId)->first();
  if($existing){
   if(!hash_equals($existing->payload_sha256,$hash))throw new LogicException('Conflicting duplicate event');
   return false;
  }
  return DB::table('payment_webhook_events')->insertOrIgnore([
   'id'=>(string)Str::uuid(),'provider'=>$provider,'external_event_id'=>$eventId,
   'payload_sha256'=>$hash,'status'=>'pending','received_at'=>now(),'created_at'=>now(),'updated_at'=>now()
  ])===1;
 }
}
