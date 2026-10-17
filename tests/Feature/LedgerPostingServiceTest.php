<?php
namespace Tests\Feature;
use App\Services\Accounting\LedgerPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;
class LedgerPostingServiceTest extends TestCase {
 use RefreshDatabase;
 private function account(string $code,string $currency='EGP'):string {
  $id=(string)Str::uuid();
  DB::table('ledger_accounts')->insert(['id'=>$id,'code'=>$code,'name'=>$code,'currency'=>$currency,'created_at'=>now(),'updated_at'=>now()]);
  return $id;
 }
 public function test_balanced_journal_is_written_atomically():void {
  $a=$this->account('test-cash');$b=$this->account('test-clearing');
  $id=app(LedgerPostingService::class)->post('sim-order-00001','Sandbox journal','EGP',[
   ['account_id'=>$a,'debit_minor'=>1000,'credit_minor'=>0],
   ['account_id'=>$b,'debit_minor'=>0,'credit_minor'=>1000]
  ]);
  $this->assertDatabaseHas('ledger_transactions',['id'=>$id,'external_key'=>'sim-order-00001']);
  $this->assertDatabaseCount('ledger_entries',2);
 }
 public function test_currency_mismatch_does_not_write_partial_journal():void {
  $a=$this->account('test-egp');$b=$this->account('test-sar','SAR');
  $this->expectException(InvalidArgumentException::class);
  try{
   app(LedgerPostingService::class)->post('sim-order-00002','Invalid currencies','EGP',[
    ['account_id'=>$a,'debit_minor'=>1000,'credit_minor'=>0],
    ['account_id'=>$b,'debit_minor'=>0,'credit_minor'=>1000]
   ]);
  } finally { $this->assertDatabaseCount('ledger_transactions',0); }
 }
 public function test_duplicate_external_key_is_rejected():void {
  $a=$this->account('dup-a');$b=$this->account('dup-b');
  $entry=[['account_id'=>$a,'debit_minor'=>1000,'credit_minor'=>0],['account_id'=>$b,'debit_minor'=>0,'credit_minor'=>1000]];
  $svc=app(LedgerPostingService::class);
  $svc->post('sim-order-00003','First','EGP',$entry);
  $this->expectException(LogicException::class);
  $svc->post('sim-order-00003','Duplicate','EGP',$entry);
 }
}
