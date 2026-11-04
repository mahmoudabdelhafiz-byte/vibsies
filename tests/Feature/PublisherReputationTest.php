<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdCampaignOffer;
use App\Models\PublisherAdPackage;
use App\Models\PublisherCampaignReview;
use App\Models\PublisherProperty;
use App\Models\User;
use App\Services\Ads\PublisherReputation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublisherReputationTest extends TestCase
{
    use RefreshDatabase;

    private function offer(string $status='confirmed'): array {
        $advertiser=User::factory()->create();
        $publisher=User::factory()->create();
        $property=PublisherProperty::create([
            'user_id'=>$publisher->id,'platform'=>'facebook','property_type'=>'page',
            'display_name'=>'Cairo page','profile_url'=>'https://www.facebook.com/example',
            'category'=>'Community','country_code'=>'EG','verification_token'=>'RATINGTEST12345',
        ]);
        $package=PublisherAdPackage::create([
            'publisher_property_id'=>$property->id,'title'=>'Post',
            'placement_type'=>'feed_post','price_minor'=>10000,'currency'=>'EGP','delivery_days'=>7,
        ]);
        $campaign=AdCampaign::create([
            'user_id'=>$advertiser->id,'title'=>'Cairo campaign',
            'brief'=>'A sample promotion for local audiences',
            'objective'=>'awareness','target_category'=>'Community','target_country'=>'EG',
            'budget_minor'=>20000,'currency'=>'EGP',
        ]);
        $offer=AdCampaignOffer::create([
            'ad_campaign_id'=>$campaign->id,'publisher_ad_package_id'=>$package->id,
            'publisher_user_id'=>$publisher->id,'agreed_price_minor'=>10000,'currency'=>'EGP',
        ]);
        $offer->forceFill(['status'=>$status])->save();
        return compact('advertiser','publisher','property','offer');
    }

    private function ratings(): array {
        return ['overall_rating'=>5,'communication_rating'=>5,'timeliness_rating'=>4,'quality_rating'=>5,'comment'=>'Delivered as agreed.'];
    }

    public function test_only_advertiser_can_review_confirmed_placement_once():void {
        config()->set('vibsies_ads.enabled',true);
        ['advertiser'=>$advertiser,'publisher'=>$publisher,'offer'=>$offer]=$this->offer();
        $this->actingAs($publisher)->post(route('ads.offer.rating',$offer),$this->ratings())->assertForbidden();
        $this->actingAs($advertiser)->post(route('ads.offer.rating',$offer),$this->ratings())->assertRedirect();
        $this->assertDatabaseHas('publisher_campaign_reviews',[
            'ad_campaign_offer_id'=>$offer->id,'moderation_status'=>'pending','overall_rating'=>5,
        ]);
        $this->actingAs($advertiser)->post(route('ads.offer.rating',$offer),$this->ratings())->assertStatus(409);
        $this->assertDatabaseCount('publisher_campaign_reviews',1);
    }

    public function test_unconfirmed_or_cancelled_offer_cannot_be_rated():void {
        config()->set('vibsies_ads.enabled',true);
        ['advertiser'=>$advertiser,'offer'=>$offer]=$this->offer('submitted');
        $this->actingAs($advertiser)->post(route('ads.offer.rating',$offer),$this->ratings())->assertStatus(409);
        $this->assertDatabaseCount('publisher_campaign_reviews',0);
    }

    public function test_rating_validation_and_feature_flag():void {
        config()->set('vibsies_ads.enabled',true);
        ['advertiser'=>$advertiser,'offer'=>$offer]=$this->offer();
        $bad=$this->ratings();$bad['overall_rating']=6;
        $this->actingAs($advertiser)->post(route('ads.offer.rating',$offer),$bad)->assertSessionHasErrors('overall_rating');
        config()->set('vibsies_ads.enabled',false);
        $this->actingAs($advertiser)->post(route('ads.offer.rating',$offer),$this->ratings())->assertNotFound();
    }

    public function test_publisher_can_submit_only_one_unverified_report():void {
        config()->set('vibsies_ads.enabled',true);
        ['advertiser'=>$advertiser,'publisher'=>$publisher,'offer'=>$offer]=$this->offer();
        $data=['measured_on'=>now()->format('Y-m-d'),'views'=>340,'reach'=>200];
        $this->actingAs($advertiser)->post(route('ads.offer.performance',$offer),$data)->assertForbidden();
        $this->actingAs($publisher)->post(route('ads.offer.performance',$offer),$data)->assertRedirect();
        $this->assertDatabaseHas('publisher_campaign_performance_reports',[
            'ad_campaign_offer_id'=>$offer->id,'evidence_source'=>'self_reported',
            'verification_status'=>'unverified','views'=>340,
        ]);
        $this->actingAs($publisher)->post(route('ads.offer.performance',$offer),$data)->assertStatus(409);
    }

    public function test_moderator_approval_is_restricted_and_does_not_create_payment():void {
        config()->set('vibsies_ads.enabled',true);
        ['advertiser'=>$advertiser,'offer'=>$offer]=$this->offer();
        $this->actingAs($advertiser)->post(route('ads.offer.rating',$offer),$this->ratings())->assertRedirect();
        $review=PublisherCampaignReview::first();
        $data=['decision'=>'approved','note'=>'Reviewed the completed campaign and feedback.'];
        $this->actingAs($advertiser)->post(route('ads.admin.review.moderate',$review),$data)->assertForbidden();
        $admin=User::factory()->create();$admin->forceFill(['is_admin'=>true])->save();
        $this->actingAs($admin)->post(route('ads.admin.review.moderate',$review),$data)->assertRedirect();
        $this->assertDatabaseHas('publisher_campaign_reviews',['id'=>$review->id,'moderation_status'=>'approved']);
        $this->assertDatabaseCount('ad_ledger_batches',0);
        $this->actingAs($admin)->post(route('ads.admin.review.moderate',$review),$data)->assertStatus(409);
    }

    public function test_no_public_score_until_three_independent_advertisers():void {
        config()->set('vibsies_ads.enabled',true);
        ['advertiser'=>$advertiser,'property'=>$property,'offer'=>$offer]=$this->offer();
        PublisherCampaignReview::create($this->ratings()+[
            'ad_campaign_offer_id'=>$offer->id,'publisher_property_id'=>$property->id,
            'advertiser_user_id'=>$advertiser->id,
        ])->forceFill(['moderation_status'=>'approved'])->save();
        $result=app(PublisherReputation::class)->summary($property);
        $this->assertSame('unrated',$result['status']);
        $this->assertNull($result['average_overall']);
        $this->assertSame(1,$result['review_count']);
    }
}
