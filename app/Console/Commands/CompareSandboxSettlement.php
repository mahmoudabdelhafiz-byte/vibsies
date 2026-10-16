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
 protected $signature='vibsies:compare-settlement {file : Local CSV file to inspect}';
 protected $description='Read-only comparison of sandbox settlement rows against payment intent references';
 public function handle():int {
  $file=(string)$this->argument('file');
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
   $this->table(['Result','Count'],[['Matched',$matched],['Missing internal reference',$missing],['Different amount, currency or state',$different],['Invalid or duplicate CSV rows',$invalid]]);
   $this->line('Read-only comparison. Source authenticity and actual settlement are NOT verified.');
   return ($missing+$different+$invalid)>0?self::FAILURE:self::SUCCESS;
  }catch(\Throwable $e){$this->error($e->getMessage());return self::FAILURE;}
  finally {fclose($handle);}
 }
}
