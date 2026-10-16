<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
/**
 * Compare an offline provider sandbox CSV to locally recorded payment intents.
 * No persistence, state transitions or provider API calls.
 */
class CompareSandboxSettlement extends Command {
 protected $signature='vibsies:compare-settlement {file : Local CSV file to inspect} {--from= : Inclusive UTC date YYYY-MM-DD} {--to= : Inclusive UTC date YYYY-MM-DD}';
 protected $description='Read-only comparison of sandbox settlement rows against payment intent references';
 private static function validDate(mixed $date):bool {
  if(!is_string($date) || !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/',$date))return false;
  $parsed=\\DateTimeImmutable::createFromFormat('!Y-m-d',$date,new \\DateTimeZone('UTC'));
  return $parsed!==false && $parsed->format('Y-m-d')===$date;
 }
 public function handle():int {
  $file=(string)$this->argument('file');
  $from=$this->option('from');$to=$this->option('to');
  // Require explicit bounds; otherwise a partial CSV creates misleading missing-reference results.
  if(!$from || !$to || !self::validDate($from) || !self::validDate($to) || $from>$to){
   $this->error('Specify a valid UTC reconciliation range with --from=YYYY-MM-DD --to=YYYY-MM-DD.');
   return self::FAILURE;
  }
  if(!is_file($file)||!is_readable($file)){ $this->error('CSV file cannot be read.');return self::FAILURE; }
  $handle=fopen($file,'rb');
  if(!$handle){$this->error('Unable to open CSV.');return self::FAILURE;}
  try {
   $header=fgetcsv($handle,0,',','"','\\');
   if($header!==['provider_reference','currency','amount_minor','status'])throw new InvalidArgumentException('Expected CSV columns: provider_reference,currency,amount_minor,status');
   $matched=0;$missing=0;$different=0;$invalid=0;$line=1;$seen=[];
   while(($row=fgetcsv($handle,0,',','"','\\'))!==false){
    $line++;
    if($line>10001){throw new InvalidArgumentException('Maximum 10,000 settlement rows per comparison.');}
    if(count($row)!==4 || strlen($row[0])>191 || $row[0]==='' || isset($seen[$row[0]]) ||
       !preg_match('/^[A-Z]{3}$/',$row[1]) || !preg_match('/^(0|[1-9][0-9]{0,14})$/',$row[2]) ||
       !in_array($row[3],['authorized','captured','refunded','failed'],true)){$invalid++;continue;}
    $seen[$row[0]]=true;
    $intent=DB::table('payment_intents')->where('provider_reference',$row[0])->first();
    if(!$intent){$missing++;continue;}
    if($intent->currency!==$row[1] || (int)$intent->amount_minor!==(int)$row[2] || $intent->status!==$row[3]){$different++;continue;}
    $matched++;
   }
   $internalMissing=0;
   // Compare in the opposite direction without exposing customer or payment data.
   DB::table('payment_intents')->whereNotNull('provider_reference')->where('created_at','>=',$from.' 00:00:00')->where('created_at','<',date('Y-m-d',strtotime($to.' +1 day')).' 00:00:00')->orderBy('id')
    ->select(['id','provider_reference'])->chunk(500,function($intents)use(&$internalMissing,$seen){
     foreach($intents as $intent){
      if(!isset($seen[$intent->provider_reference]))$internalMissing++;
     }
    });
   $this->table(['Result','Count'],[['Matched',$matched],['Missing internal reference',$missing],['Internal reference absent from CSV',$internalMissing],['Different amount, currency or state',$different],['Invalid or duplicate CSV rows',$invalid]]);
   $this->line('Read-only comparison. Source authenticity and actual settlement are NOT verified.');
   return ($missing+$internalMissing+$different+$invalid)>0?self::FAILURE:self::SUCCESS;
  }catch(\Throwable $e){$this->error($e->getMessage());return self::FAILURE;}
  finally {fclose($handle);}
 }
}
