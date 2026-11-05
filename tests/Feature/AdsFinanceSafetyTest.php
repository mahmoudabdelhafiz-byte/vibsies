<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdCampaignOffer;
use App\Models\PublisherAdPackage;
use App\Models\PublisherProperty;
use App\Models\User;
use App\Services\Ads\LedgerPosting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdsFinanceSafetyTest extends TestCase {
    use RefreshDatabase;

    private function confirmedOffer():AdCampaignOffer {
        $advertiser=User::factory()->create();
        $publisher=User::factory()->create();
        $property=PublisherProperty::create([
            'user_id'=>$publisher->id,'platform'=>'facebook','property_type'=>'page',
            'display_name'=>'New Cairo','profile_url'=>'https://www.facebook.com/newcairo',
            'category'=>'Community','country_code'=>'EG','verification_token'=>'FINANCE123456789',
        ]);
        $package=PublisherAdPackage::create([
            'publisher_property_id'=>$property->id,'title'=>'Post',
            'placement_type'=>'feed_post','price_minor'=>50000,
            'currency'=>'EGP','delivery_days'=>7,
        ]);
        $campaign=AdCampaign::create([
            'user_id'=>$advertiser->id,'title'=>'Local offer',
            'brief'=>'An initial testing campaign for Cairo',
            'objective'=>'awareness','target_category'=>'Community',
            'target_country'=>'EG','budget_minor'=>100000,'currency'=>'EGP',
        ]);
        $offer=AdCampaignOffer::create([
            'ad_campaign_id'=>$campaign->id,'publisher_ad_package_id'=>$package->id,
            'publisher_user_id'=>$publisher->id,
            'agreed_price_minor'=>50000,'currency'=>'EGP',
        ]);
        $offer->forceFill(['status'=>'confirmed'])->save();
        return $offer;
    }

    public function test_finance_posting_disabled_by_default():void {
        config()->set('vibsies_ads.enabled',true);
        config()->set('vibsies_ads.finance_posting_enabled',false);
        $offer=$this->confirmedOffer();
        try {
            app(LedgerPosting::class)->postVerifiedSettlement($offer->id);
            $this->fail('Posting should remain disabled.');
        } catch(\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(503,$e->getStatusCode());
        }
        $this->assertDatabaseCount('ad_ledger_batches',0);
    }

    public function test_delivery_confirmation_alone_does_not_allow_financial_posting():void {
        config()->set('vibsies_ads.enabled',true);
        config()->set('vibsies_ads.finance_posting_enabled',true);
        $offer=$this->confirmedOffer();
        try {
            app(LedgerPosting::class)->postVerifiedSettlement($offer->id);
            $this->fail('Captured payment verification is required.');
        } catch(\DomainException $e) {
            $this->assertStringContainsString('captured payment',$e->getMessage());
        }
        $this->assertDatabaseCount('ad_ledger_batches',0);
        $this->assertDatabaseCount('ad_ledger_entries',0);
    }
}
