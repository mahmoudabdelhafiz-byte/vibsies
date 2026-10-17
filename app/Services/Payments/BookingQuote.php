<?php
namespace App\Services\Payments;
use InvalidArgumentException;
/**
 * Money amounts are integers in minor currency units. No payment network calls.
 * The current customer-side platform fee is 15%, rounded half up.
 */
final class BookingQuote {
 public function calculate(int $priceMinor,string $currency):array {
  if($priceMinor<1 || $priceMinor>100000000 || !in_array($currency,['EGP','SAR'],true)){
   throw new InvalidArgumentException('Unsupported booking amount or currency.');
  }
  $fee=intdiv($priceMinor*15+50,100);
  return [
   'price_minor'=>$priceMinor,
   'fee_minor'=>$fee,
   'total_minor'=>$priceMinor+$fee,
   'currency'=>$currency,
   'payment_status'=>'disabled'
  ];
 }
}
