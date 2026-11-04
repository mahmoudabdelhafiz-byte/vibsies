<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\PublisherAdPackage;
use App\Models\PublisherProperty;
use App\Models\User;
use App\Services\Ads\PublisherMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvertiserAdsTest extends TestCase {
    use RefreshDatabase;

    private function payload(): array {
        return [
            'title'=>'New Cairo restaurant launch',
            'brief'=>'Reach residents interested in restaurant openings in New Cairo.',
            'objective'=>'awareness',
            'target_category'=>'Local community',
            'target_country'=>'EG',
            'target_city'=>'New Cairo',
            'placement_type'=>'feed_post',
            'budget'=>'1000.00',
            'currency'=>'EGP',
        ];
    }

    public function test_campaign_is_draft_and_does_not_charge_or_reserve_funds(): void {
        config()->set('vibsies_ads.enabled',true);
        $user=User::factory()->create();
        $this->actingAs($user)->post(route('ads.advertiser.store'),$this->payload())->assertRedirect();
        $this->assertDatabaseHas('ad_campaigns',[
            'user_id'=>$user->id,'status'=>'draft','budget_minor'=>100000,
        ]);
    }

    public function test_another_advertiser_cannot_view_campaign(): void {
        config()->set('vibsies_ads.enabled',true);
        $owner=User::factory()->create();
        $campaign=AdCampaign::create([
            'user_id'=>$owner->id,
            'title'=>'Test campaign','brief'=>'Relevant local restaurant awareness.',
            'objective'=>'awareness','target_category'=>'Local community',
            'target_country'=>'EG','target_city'=>'New Cairo',
            'budget_minor'=>100000,'currency'=>'EGP',
        ]);
        $this->actingAs(User::factory()->create())
            ->get(route('ads.advertiser.show',$campaign))->assertForbidden();
    }

    public function test_country_currency_mismatch_is_rejected(): void {
        config()->set('vibsies_ads.enabled',true);
        $payload=$this->payload();
        $payload['currency']='SAR';
        $this->actingAs(User::factory()->create())
            ->post(route('ads.advertiser.store'),$payload)
            ->assertSessionHasErrors('currency');
    }

    public function test_matcher_excludes_unapproved_inventory(): void {
        $owner=User::factory()->create();
        $property=PublisherProperty::create([
            'user_id'=>$owner->id,'platform'=>'facebook','property_type'=>'page',
            'display_name'=>'Community','profile_url'=>'https://www.facebook.com/example',
            'category'=>'Local community','country_code'=>'EG','city'=>'New Cairo',
            'verification_token'=>'UNIQUECODE123456',
        ]);
        $property->forceFill(['verification_status'=>'verified'])->save();
        $package=PublisherAdPackage::create([
            'publisher_property_id'=>$property->id,'title'=>'Sponsored feed post',
            'placement_type'=>'feed_post','price_minor'=>50000,
            'currency'=>'EGP','delivery_days'=>7,
        ]);
        $campaign=AdCampaign::create([
            'user_id'=>$owner->id,'title'=>'Restaurant campaign',
            'brief'=>'Looking to promote a new restaurant.',
            'objective'=>'awareness','target_category'=>'Local community',
            'target_country'=>'EG','target_city'=>'New Cairo',
            'placement_type'=>'feed_post','budget_minor'=>100000,'currency'=>'EGP',
        ]);
        $this->assertCount(0,app(PublisherMatcher::class)->recommend($campaign));
        $package->forceFill(['moderation_status'=>'approved','is_active'=>true])->save();
        $result=app(PublisherMatcher::class)->recommend($campaign);
        $this->assertCount(1,$result);
        $this->assertContains('Matching content category',$result->first()['reasons']);
        $this->assertContains('Insufficient verified recent engagement data',$result->first()['reasons']);
    }

    public function test_campaign_endpoints_are_feature_flagged(): void {
        config()->set('vibsies_ads.enabled',false);
        $this->actingAs(User::factory()->create())
            ->get(route('ads.advertiser.index'))->assertNotFound();
    }
}
