<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdCampaignOffer;
use App\Models\PublisherAdPackage;
use App\Models\PublisherProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdCampaignWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array {
        $advertiser=User::factory()->create();
        $publisher=User::factory()->create();
        $property=PublisherProperty::create([
            'user_id'=>$publisher->id,
            'platform'=>'facebook','property_type'=>'page',
            'display_name'=>'New Cairo',
            'profile_url'=>'https://www.facebook.com/newcairo',
            'category'=>'Community','country_code'=>'EG','city'=>'Cairo',
            'verification_token'=>'WORKFLOWTEST1234',
        ]);
        $property->forceFill(['verification_status'=>'verified'])->save();
        $package=PublisherAdPackage::create([
            'publisher_property_id'=>$property->id,
            'title'=>'Sponsored post','placement_type'=>'feed_post',
            'price_minor'=>50000,'currency'=>'EGP','delivery_days'=>7,
        ]);
        $package->forceFill(['is_active'=>true,'moderation_status'=>'approved'])->save();
        $campaign=AdCampaign::create([
            'user_id'=>$advertiser->id,'title'=>'New Cairo launch',
            'brief'=>'We want an audience for our new Cairo business.',
            'objective'=>'awareness','target_category'=>'Community',
            'target_country'=>'EG','target_city'=>'Cairo',
            'placement_type'=>'feed_post','budget_minor'=>100000,'currency'=>'EGP',
        ]);
        return compact('advertiser','publisher','property','package','campaign');
    }

    public function test_cannot_send_offer_before_campaign_approval(): void {
        config()->set('vibsies_ads.enabled',true);
        ['advertiser'=>$advertiser,'package'=>$package,'campaign'=>$campaign]=$this->fixture();
        $this->actingAs($advertiser)->post(route('ads.campaign.offer',$campaign),[
            'package_id'=>$package->id,
        ])->assertStatus(409);
        $this->assertDatabaseCount('ad_campaign_offers',0);
    }

    public function test_approved_campaign_can_send_preliminary_offer(): void {
        config()->set('vibsies_ads.enabled',true);
        ['advertiser'=>$advertiser,'package'=>$package,'campaign'=>$campaign]=$this->fixture();
        $campaign->forceFill(['status'=>'approved'])->save();
        $this->actingAs($advertiser)->post(route('ads.campaign.offer',$campaign),[
            'package_id'=>$package->id,
        ])->assertRedirect();
        $this->assertDatabaseHas('ad_campaign_offers',[
            'ad_campaign_id'=>$campaign->id,'publisher_ad_package_id'=>$package->id,
            'status'=>'offered','agreed_price_minor'=>50000,
        ]);
        $this->actingAs($advertiser)->post(route('ads.campaign.offer',$campaign),[
            'package_id'=>$package->id,
        ])->assertStatus(409);
    }

    public function test_only_the_invited_publisher_can_accept(): void {
        config()->set('vibsies_ads.enabled',true);
        ['advertiser'=>$advertiser,'publisher'=>$publisher,'package'=>$package,'campaign'=>$campaign]=$this->fixture();
        $offer=AdCampaignOffer::create([
            'ad_campaign_id'=>$campaign->id,
            'publisher_ad_package_id'=>$package->id,
            'publisher_user_id'=>$publisher->id,
            'agreed_price_minor'=>50000,'currency'=>'EGP',
        ]);
        $this->actingAs($advertiser)->post(route('ads.publisher.offer.decide',$offer),[
            'decision'=>'accepted',
        ])->assertForbidden();
        $this->actingAs($publisher)->post(route('ads.publisher.offer.decide',$offer),[
            'decision'=>'accepted',
        ])->assertRedirect();
        $this->assertDatabaseHas('ad_campaign_offers',['id'=>$offer->id,'status'=>'accepted']);
    }

    public function test_publisher_submits_publication_and_advertiser_confirms(): void {
        config()->set('vibsies_ads.enabled',true);
        ['advertiser'=>$advertiser,'publisher'=>$publisher,'package'=>$package,'campaign'=>$campaign]=$this->fixture();
        $offer=AdCampaignOffer::create([
            'ad_campaign_id'=>$campaign->id,
            'publisher_ad_package_id'=>$package->id,
            'publisher_user_id'=>$publisher->id,
            'agreed_price_minor'=>50000,'currency'=>'EGP',
        ]);
        $offer->forceFill(['status'=>'accepted'])->save();
        $this->actingAs($publisher)->post(route('ads.publisher.offer.publish',$offer),[
            'published_url'=>'https://www.facebook.com/newcairo/posts/123',
            'disclosure_confirmed'=>'1',
        ])->assertRedirect();
        $this->assertDatabaseHas('ad_campaign_offers',['id'=>$offer->id,'status'=>'submitted']);
        $this->actingAs($advertiser)->post(route('ads.offer.review',$offer),[
            'decision'=>'confirmed',
        ])->assertRedirect();
        $this->assertDatabaseHas('ad_campaign_offers',['id'=>$offer->id,'status'=>'confirmed']);
    }

    public function test_publishing_requires_sponsorship_disclosure_confirmation(): void {
        config()->set('vibsies_ads.enabled',true);
        ['publisher'=>$publisher,'package'=>$package,'campaign'=>$campaign]=$this->fixture();
        $offer=AdCampaignOffer::create([
            'ad_campaign_id'=>$campaign->id,'publisher_ad_package_id'=>$package->id,
            'publisher_user_id'=>$publisher->id,'agreed_price_minor'=>50000,'currency'=>'EGP',
        ]);
        $offer->forceFill(['status'=>'accepted'])->save();
        $this->actingAs($publisher)->post(route('ads.publisher.offer.publish',$offer),[
            'published_url'=>'https://www.facebook.com/newcairo/posts/123',
        ])->assertSessionHasErrors('disclosure_confirmed');
        $this->assertDatabaseHas('ad_campaign_offers',['id'=>$offer->id,'status'=>'accepted']);
    }

    public function test_workflow_is_disabled_by_default(): void {
        config()->set('vibsies_ads.enabled',false);
        ['advertiser'=>$advertiser,'campaign'=>$campaign]=$this->fixture();
        $this->actingAs($advertiser)->post(route('ads.campaign.submit',$campaign))->assertNotFound();
    }
}
