<?php

namespace App\Services\Ads;

use App\Models\AdCampaignOffer;
use App\Models\AdLedgerBatch;
use App\Models\AdPaymentRecord;
use App\Models\PublisherRewardEntitlement;
use Illuminate\Support\Facades\DB;

class LedgerPosting {
    /**
     * INTERNAL ONLY. No public route, payout integration or payment capture.
     * A vetted provider adapter must establish verified captured funds.
     */
    public function postVerifiedSettlement(string $offerId): AdLedgerBatch {
        abort_unless(config('vibsies_ads.enabled') && config('vibsies_ads.finance_posting_enabled',false),503,'Financial posting is disabled.');
        return DB::transaction(function()use($offerId) {
            $offer=AdCampaignOffer::with('package.property')
                ->whereKey($offerId)->lockForUpdate()->firstOrFail();
            if(!in_array($offer->status,['confirmed','resolved_confirmed'],true)) {
                throw new \DomainException('Delivery has not been accepted or dispute resolved.');
            }
            $prior=AdLedgerBatch::where('ad_campaign_offer_id',$offer->id)->first();
            if($prior) return $prior;
            $payment=AdPaymentRecord::where('ad_campaign_offer_id',$offer->id)->lockForUpdate()->first();
            if(!$payment || $payment->status!=='captured' || !$payment->verified_at
               || !$payment->provider || !$payment->provider_reference
               || $payment->amount_minor!==$offer->agreed_price_minor
               || $payment->currency!==$offer->currency) {
                throw new \DomainException('Verified fully captured payment is required.');
            }
            $property=$offer->package->property;
            $entitlement=PublisherRewardEntitlement::where('publisher_property_id',$property->id)
                ->lockForUpdate()->first();
            $eligible=$entitlement
                && $entitlement->status==='eligible'
                && $entitlement->eligible_from <= now()
                && $entitlement->expires_at >= now()
                && $entitlement->placements_used < $entitlement->placement_limit
                && $entitlement->promotion
                && $entitlement->promotion->status==='approved';
            $percent=$eligible ? $entitlement->publisher_share_percent : (int)config('vibsies_ads.publisher_base_share_percent',70);
            $amount=app(RevenueSplit::class)->calculate($payment->amount_minor,$percent);
            $batch=AdLedgerBatch::create([
                'ad_campaign_offer_id'=>$offer->id,
                'ad_payment_record_id'=>$payment->id,
                'publisher_reward_entitlement_id'=>$eligible?$entitlement->id:null,
                'publisher_share_percent'=>$percent,
                'currency'=>$payment->currency,
                'gross_minor'=>$amount['gross_minor'],
                'status'=>'held',
            ]);
            foreach([
                ['receivable','debit',$amount['gross_minor']],
                ['publisher_payable','credit',$amount['publisher_minor']],
                ['platform_revenue','credit',$amount['platform_minor']],
            ] as [$account,$direction,$minor]) {
                $batch->entries()->create([
                    'account'=>$account,'direction'=>$direction,
                    'amount_minor'=>$minor,'currency'=>$payment->currency,
                ]);
            }
            if($eligible) {
                $entitlement->increment('placements_used');
            }
            return $batch->load('entries');
        });
    }
}
