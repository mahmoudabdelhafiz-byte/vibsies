<?php
namespace App\Services\Payments;
use LogicException;
final class DisabledPaymentGateway {
 public function checkout():never {throw new LogicException('Payments are disabled.');}
 public function capture():never {throw new LogicException('Payments are disabled.');}
 public function refund():never {throw new LogicException('Refunds are disabled.');}
 public function payout():never {throw new LogicException('Payouts are disabled.');}
}