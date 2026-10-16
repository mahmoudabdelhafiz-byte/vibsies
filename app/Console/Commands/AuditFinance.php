<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class AuditFinance extends Command {
 protected $signature='vibsies:audit-finance {--limit=500 : Maximum ledger transactions to inspect}';
 protected $description='Read-only audit of ledger balancing and pending payment events';
 public function handle():int {
  $limit=max(1,min(5000,(int)$this->option('limit')));
  $unbalanced=0;$inspected=0;
  $transactions=DB::table('ledger_transactions')->orderBy('id')->limit($limit)->get(['id','external_key']);
  foreach($transactions as $transaction){
   $inspected++;
   $entries=DB::table('ledger_entries')->where('ledger_transaction_id',$transaction->id)
     ->selectRaw('COUNT(*) as entry_count, COALESCE(SUM(debit_minor),0) as debit_total, COALESCE(SUM(credit_minor),0) as credit_total')->first();
   if((int)$entries->entry_count<2 || (int)$entries->debit_total!==(int)$entries->credit_total){
    $unbalanced++;$this->warn('Journal failed balancing check: '.$transaction->external_key);
   }
  }
  $pending=DB::table('payment_webhook_events')->where('status','pending')->count();
  $reserved=DB::table('payment_idempotency_keys')->where('status','reserved')->count();
  $this->table(['Metric','Count'],[
   ['Ledger transactions inspected',$inspected],
   ['Unbalanced or incomplete journals',$unbalanced],
   ['Pending payment event records',$pending],
   ['Reserved idempotency keys',$reserved],
  ]);
  $this->line('Read-only report; payment provider settlement reconciliation is not yet implemented.');
  return $unbalanced?self::FAILURE:self::SUCCESS;
 }
}
