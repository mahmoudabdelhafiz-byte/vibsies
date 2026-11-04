<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdCampaignOffer;
use App\Models\PublisherAdPackage;
use App\Models\PublisherProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdOfferLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function setupOffer(string $status='offered'): array {
        $advertiser=User::factory()->create();
        $publisher=User::factory()->create();
        $property=PublisherProperty::create([
            'user_id'=>$publisher->id,'platform'=>'facebook','property_type'=>'page',
            'display_name'=>'New Cairo','profile_url'=>'https://www.facebook.com/example',
            'category'=>'Community','country_code'=>'EG','verification_token'=>'LIFECYCLETEST123',
        ]);
        $package=PublisherAdPackage::create([
            'publisher_property_id'=>$property->id,'title'=>'Feed placement',
            'placement_type'=>'feed_post','price_minor'=>50000,'currency'=>'EGP','delivery_days'=>7,
        ]);
        $campaign=AdCampaign::create([
            'user_id'=>$advertiser->id,'title'=>'New Cairo brand',
            'brief'=>'A legitimate local advertising campaign',
            'objective'=>'awareness','target_category'=>'Community',
            'target_country'=>'EG','budget_minor'=>100000,'currency'=>'EGP',
        ]);
        $offer=AdCampaignOffer::create([
            'ad_campaign_id'=>$campaign->id,'publisher_ad_package_id'=>$package->id,
            'publisher_user_id'=>$publisher->id,'agreed_price_minor'=>50000,
            'currency'=>'EGP','expires_at'=>now()->addDays(7),
        ]);
        $offer->forceFill(['status'=>$status])->save();
        return compact('advertiser','publisher','offer');
    }

    public function test_advertiser_can_withdraw_unaccepted_offer_with_reason():void {
        config()->set('vibsies_ads.enabled',true);
        ['advertiser'=>$advertiser,'offer'=>$offer]=$this->setupOffer();
        $this->actingAs($advertiser)->post(route('ads.offer.cancel',$offer),[
            'reason'=>'The product launch has been postponed.',
        ])->assertRedirect();
        $this->assertDatabaseHas('ad_campaign_offers',['id'=>$offer->id,'status'=>'cancelled']);
        $this->assertDatabaseHas('ad_offer_events',[
            'ad_campaign_offer_id'=>$offer->id,'event_type'=>'cancelled',
            'actor_id'=>$advertiser->id,
        ]);
    }

    public function test_advertiser_cannot_cancel_accepted_offer():void {
        config()->set('vibsies_ads.enabled',true);
        ['advertiser'=>$advertiser,'offer'=>$offer]=$this->setupOffer('accepted');
        $this->actingAs($advertiser)->post(route('ads.offer.cancel',$offer),[
            'reason'=>'Want to stop after the publisher accepted.',
        ])->assertStatus(409);
    }

    public function test_publisher_can_cancel_before_publication():void {
        config()->set('vibsies_ads.enabled',true);
        ['publisher'=>$publisher,'offer'=>$offer]=$this->setupOffer('accepted');
        $this->actingAs($publisher)->post(route('ads.offer.cancel',$offer),[
            'reason'=>'The publication is no longer possible.',
        ])->assertRedirect();
        $this->assertDatabaseHas('ad_campaign_offers',['id'=>$offer->id,'status'=>'cancelled']);
    }

    public function test_unrelated_user_cannot_cancel_offer():void {
        config()->set('vibsies_ads.enabled',true);
        ['offer'=>$offer]=$this->setupOffer();
        $this->actingAs(User::factory()->create())->post(route('ads.offer.cancel',$offer),[
            'reason'=>'Not my advertising placement.',
        ])->assertForbidden();
    }

    public function test_non_admin_cannot_resolve_dispute():void {
        config()->set('vibsies_ads.enabled',true);
        ['advertiser'=>$advertiser,'offer'=>$offer]=$this->setupOffer('disputed');
        $this->actingAs($advertiser)->post(route('ads.admin.offer.resolve',$offer),[
            'decision'=>'resolved_confirmed','note'=>'This is enough evidence to confirm delivery.',
        ])->assertForbidden();
    }

    public function test_overdue_offer_cannot_be_accepted():void {
        config()->set('vibsies_ads.enabled',true);
        ['publisher'=>$publisher,'offer'=>$offer]=$this->setupOffer();
        $offer->forceFill(['expires_at'=>now()->subDay()])->save();
        $this->actingAs($publisher)->post(route('ads.publisher.offer.decide',$offer),[
            'decision'=>'accepted',
        ])->assertStatus(409);
    }

    public function test_lifecycle_feature_flag_blocks_cancellation():void {
        config()->set('vibsies_ads.enabled',false);
        ['advertiser'=>$advertiser,'offer'=>$offer]=$this->setupOffer();
        $this->actingAs($advertiser)->post(route('ads.offer.cancel',$offer),[
            'reason'=>'I want to cancel this offer.',
        ])->assertNotFound();
    }
}
