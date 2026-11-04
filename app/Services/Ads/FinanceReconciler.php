<?php

namespace App\Services\Ads;

use App\Models\AdLedgerBatch;
use App\Models\AdPaymentRecord;
use Illuminate\Support\Collection;

/**
 * Read-only internal diagnostics; never approves funds, releases payouts or
 * reconciles against an external provider statement.
 */
class FinanceReconciler {
    public function inspectBatch(AdLedgerBatch $batch): array {
        $batch->loadMissing(['entries','offer']);
        $errors=[];
        $debit=0;
        $credit=0;
        foreach($batch->entries as $entry) {
            if($entry->currency!==$batch->currency || $entry->amount_minor<0) {
                $errors[]='INVALID_LEDGER_ENTRY';
                continue;
            }
            if($entry->direction==='debit') $debit+=$entry->amount_minor;
            elseif($entry->direction==='credit') $credit+=$entry->amount_minor;
            else $errors[]='INVALID_DIRECTION';
        }
        if($debit!==$credit || $debit!==$batch->gross_minor) $errors[]='UNBALANCED_BATCH';
        if($batch->entries->count()!==3) $errors[]='UNEXPECTED_ENTRY_COUNT';
        $payment=AdPaymentRecord::find($batch->ad_payment_record_id);
        if(!$payment || $payment->status!=='captured' || !$payment->verified_at
          || !$payment->provider_reference || !$payment->provider
          || $payment->amount_minor!==$batch->gross_minor
          || $payment->currency!==$batch->currency
          || $payment->ad_campaign_offer_id!==$batch->ad_campaign_offer_id) {
            $errors[]='PAYMENT_PROOF_MISMATCH';
        }
        if(!$batch->offer || !in_array($batch->offer->status,['confirmed','resolved_confirmed'],true)) {
            $errors[]='DELIVERY_STATUS_MISMATCH';
        }
        $parts=(new RevenueSplit())->calculate($batch->gross_minor,$batch->publisher_share_percent);
        $byAccount=$batch->entries->keyBy('account');
        if(($byAccount->get('publisher_payable')?->amount_minor)!==$parts['publisher_minor'] ||
           ($byAccount->get('platform_revenue')?->amount_minor)!==$parts['platform_minor'] ||
           ($byAccount->get('settlement_clearing')?->amount_minor)!==$parts['gross_minor']) {
            $errors[]='SPLIT_MISMATCH';
        }
        return ['batch_id'=>$batch->id,'valid'=>count($errors)===0,'exceptions'=>array_values(array_unique($errors))];
    }

    public function inspectRecent(int $limit=100): Collection {
        return AdLedgerBatch::with(['entries','offer'])->latest()->limit(min(max($limit,1),500))
            ->get()->map(fn($batch)=>$this->inspectBatch($batch));
    }
}