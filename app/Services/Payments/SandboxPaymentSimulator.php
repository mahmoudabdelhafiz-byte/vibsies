<?php
namespace App\Services\Payments;
use LogicException;
/**
 * Deterministic test double. No external network access or financial posting.
 * Explicitly forbidden in production.
 */
final class SandboxPaymentSimulator {
 public function __construct(private BookingQuote $quotes){}
 public function simulate(int $priceMinor,string $currency,string $scenario='success'):array {
  if(app()->environment('production'))throw new LogicException('Payment simulation is forbidden in production.');
  if(!in_array($scenario,['success','declined','timeout'],true))throw new LogicException('Unknown sandbox scenario.');
  $quote=$this->quotes->calculate($priceMinor,$currency);
  return [
   'mode'=>'simulated',
   'scenario'=>$scenario,
   'result'=>match($scenario){'success'=>'authorized_simulation','declined'=>'declined_simulation','timeout'=>'timeout_simulation'},
   'quote'=>$quote,
   'funds_moved'=>false,
   'payment_intent_created'=>false
  ];
 }
 public function capture():never {throw new LogicException('Capture unavailable in simulator.');}
 public function refund():never {throw new LogicException('Refund unavailable in simulator.');}
 public function payout():never {throw new LogicException('Payout unavailable in simulator.');}
}
