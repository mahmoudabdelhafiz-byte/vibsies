<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdCampaignOffer;
use App\Models\AdLedgerBatch;
use App\Models\AdPaymentRecord;
use App\Models\PublisherAdPackage;
use App\Models\PublisherProperty;
use App\Models\User;
use App\Services\Ads\FinanceReconciler;
use App\Services\Ads\Payments\UnavailableGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdsReconciliationTest extends TestCase {
    use RefreshDatabase;

    private function batch(): AdLedgerBatch {
        $advertiser=User::factory()->create();
        $publisher=User::factory()->create();
        $property=PublisherProperty::create([
            'user_id'=>$publisher->id,'platform'=>'facebook','property_type'=>'page',
            'display_name'=>'Cairo','profile_url'=>'https://www.facebook.com/example',
            'category'=>'Community','country_code'=>'EG','verification_token'=>'RECONCILETEST123',
        ]);
        $package=PublisherAdPackage::create([
            'publisher_property_id'=>$property->id,'title'=>'Sponsored post',
            'placement_type'=>'feed_post','price_minor'=>10000,'currency'=>'EGP','delivery_days'=>7,
        ]);
        $campaign=AdCampaign::create([
            'user_id'=>$advertiser->id,'title'=>'Test campaign',
            'brief'=>'Local audience awareness campaign.',
            'objective'=>'awareness','target_category'=>'Community',
            'target_country'=>'EG','budget_minor'=>10000,'currency'=>'EGP',
        ]);
        $offer=AdCampaignOffer::create([
            'ad_campaign_id'=>$campaign->id,'publisher_ad_package_id'=>$package->id,
            'publisher_user_id'=>$publisher->id,'agreed_price_minor'=>10000,'currency'=>'EGP',
        ]);
        $offer->forceFill(['status'=>'confirmed'])->save();
        $payment=new AdPaymentRecord();
        $payment->id=(string)\Illuminate\Support\Str::uuid();
        $payment->ad_campaign_offer_id=$offer->id;
        $payment->provider='test_fixture';
        $payment->provider_reference='test-confirmation';
        $payment->currency='EGP';
        $payment->amount_minor=10000;
        $payment->status='captured';
        $payment->verified_at=now();
        $payment->save();
        $batch=AdLedgerBatch::create([
            'ad_campaign_offer_id'=>$offer->id,
            'ad_payment_record_id'=>$payment->id,
            'publisher_share_percent'=>70,'currency'=>'EGP',
            'gross_minor'=>10000,'status'=>'held',
        ]);
        foreach([['settlement_clearing','debit',10000],['publisher_payable','credit',7000],['platform_revenue','credit',3000]] as [$account,$direction,$amount]) {
            $batch->entries()->create(['account'=>$account,'direction'=>$direction,'amount_minor'=>$amount,'currency'=>'EGP']);
        }
        return $batch;
    }

    public function test_balanced_internal_fixture_passes_diagnostics():void {
        $batch=$this->batch();
        $result=app(FinanceReconciler::class)->inspectBatch($batch);
        $this->assertTrue($result['valid']);
        $this->assertSame([],$result['exceptions']);
    }

    public function test_mismatched_publisher_share_is_detected():void {
        $batch=$this->batch();
        $batch->entries()->where('account','publisher_payable')->update(['amount_minor'=>6000]);
        $result=app(FinanceReconciler::class)->inspectBatch($batch->fresh());
        $this->assertFalse($result['valid']);
        $this->assertContains('UNBALANCED_BATCH',$result['exceptions']);
        $this->assertContains('SPLIT_MISMATCH',$result['exceptions']);
    }

    public function test_missing_provider_proof_is_detected():void {
        $batch=$this->batch();
        AdPaymentRecord::whereKey($batch->ad_payment_record_id)->update(['verified_at'=>null]);
        $result=app(FinanceReconciler::class)->inspectBatch($batch->fresh());
        $this->assertFalse($result['valid']);
        $this->assertContains('PAYMENT_PROOF_MISMATCH',$result['exceptions']);
    }

    public function test_default_gateway_cannot_start_checkout():void {
        $this->expectException(\LogicException::class);
        $offer=$this->batch()->offer;
        (new UnavailableGateway())->createCheckout($offer);
    }

    public function test_default_gateway_cannot_accept_unsigned_webhook():void {
        $this->expectException(\LogicException::class);
        (new UnavailableGateway())->verifyWebhook('{}',[]);
    }
}
