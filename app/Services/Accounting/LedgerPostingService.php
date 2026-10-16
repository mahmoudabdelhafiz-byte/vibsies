<?php
namespace App\Services\Accounting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
final class LedgerPostingService {
 public function __construct(private LedgerValidator $validator){}
 /** Development-only journal writer; never calls a payment provider. */
 public function post(string $externalKey,string $description,string $currency,array $entries):string {
  if(app()->environment('production'))throw new LogicException('Ledger postings disabled pending financial reconciliation approval.');
  if(strlen($externalKey)<8 || strlen($externalKey)>191 || !preg_match('/^[A-Z]{3}$/',$currency))throw new InvalidArgumentException('Invalid journal identification.');
  $this->validator->validate($entries);
  foreach($entries as $entry){
   if(!isset($entry['account_id']) || !is_string($entry['account_id']))throw new InvalidArgumentException('Missing account ID.');
  }
  return DB::transaction(function()use($externalKey,$description,$currency,$entries){
   if(DB::table('ledger_transactions')->where('external_key',$externalKey)->exists())throw new LogicException('Duplicate ledger transaction.');
   $accounts=DB::table('ledger_accounts')->whereIn('id',array_column($entries,'account_id'))->get()->keyBy('id');
   foreach($entries as $entry){
    $account=$accounts->get($entry['account_id']);
    if(!$account || $account->currency!==$currency)throw new InvalidArgumentException('Ledger account currency mismatch.');
   }
   $id=(string)Str::uuid();
   DB::table('ledger_transactions')->insert(['id'=>$id,'external_key'=>$externalKey,'description'=>$description,'created_at'=>now(),'updated_at'=>now()]);
   foreach($entries as $entry)DB::table('ledger_entries')->insert([
    'id'=>(string)Str::uuid(),'ledger_transaction_id'=>$id,'ledger_account_id'=>$entry['account_id'],
    'debit_minor'=>$entry['debit_minor'],'credit_minor'=>$entry['credit_minor'],'created_at'=>now(),'updated_at'=>now()
   ]);
   return $id;
  });
 }
}
