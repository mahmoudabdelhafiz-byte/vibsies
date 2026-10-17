<?php
namespace App\Services\Accounting;
use InvalidArgumentException;
final class LedgerValidator {
 public function validate(array $entries):void {
  if(count($entries)<2)throw new InvalidArgumentException('At least two ledger entries required');
  $debits=0;$credits=0;
  foreach($entries as $entry){$d=$entry['debit_minor']??null;$c=$entry['credit_minor']??null;
   if(!is_int($d)||!is_int($c)||$d<0||$c<0||($d>0&&$c>0)||($d===0&&$c===0))throw new InvalidArgumentException('Invalid ledger entry');
   $debits+=$d;$credits+=$c;
  }
  if($debits!==$credits)throw new InvalidArgumentException('Unbalanced ledger transaction');
 }
}