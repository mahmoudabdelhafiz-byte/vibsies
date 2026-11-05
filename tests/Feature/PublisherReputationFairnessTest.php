<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\PublisherProperty;
use App\Models\PublisherAdPackage;
use App\Models\AdCampaign;
use App\Models\AdCampaignOffer;
use App\Models\PublisherCampaignReview;
use App\Services\Ads\PublisherReputation;
class PublisherReputationFairnessTest extends TestCase {
 use RefreshDatabase;
 public function test_multiple_reviews_from_one_advertiser_have_equal_weight():void {
  $publisher=User::factory()->create();
  $property=PublisherProperty::create(['user_id'=>$publisher->id,'platform'=>'facebook','property_type'=>'page','display_name'=>'Publisher','profile_url'=>'https://www.facebook.com/publisher','category'=>'Community','country_code'=>'EG','verification_token'=>'FAIRNESSCODE123']);
  $package=PublisherAdPackage::create(['publisher_property_id'=>$property->id,'title'=>'Post','placement_type'=>'feed_post','price_minor'=>10000,'currency'=>'EGP','delivery_days'=>7]);
  foreach([5,5,5,5,1,3] as $i=>$stars) {
   $advertiser=($i < 4)?($advertiser??=User::factory()->create()):User::factory()->create();
   $campaign=AdCampaign::create(['user_id'=>$advertiser->id,'title'=>'Campaign '.$i,'brief'=>'An approved published campaign with evidence','objective'=>'awareness','target_category'=>'Community','target_country'=>'EG','currency'=>'EGP','budget_minor'=>20000]);
   $offer=AdCampaignOffer::create(['ad_campaign_id'=>$campaign->id,'publisher_ad_package_id'=>$package->id,'publisher_user_id'=>$publisher->id,'agreed_price_minor'=>10000,'currency'=>'EGP']);
   $offer->forceFill(['status'=>'confirmed'])->save();
   $review=PublisherCampaignReview::create(['ad_campaign_offer_id'=>$offer->id,'publisher_property_id'=>$property->id,'advertiser_user_id'=>$advertiser->id,'overall_rating'=>$stars,'communication_rating'=>$stars,'quality_rating'=>$stars,'timeliness_rating'=>$stars]);
   $review->forceFill(['moderation_status'=>'approved'])->save();
  }
  $summary=app(PublisherReputation::class)->summary($property);
  $this->assertSame('rated',$summary['status']);
  $this->assertSame(6,$summary['review_count']);
  $this->assertSame(3,$summary['independent_advertisers']);
  $this->assertEquals(3.0,$summary['average_overall']);
  $property->forceFill(['verification_status'=>'verified'])->save();
  config()->set('vibsies_ads.enabled',true);
  config()->set('vibsies_ads.public_ratings_enabled',true);
  $this->get(route('seo.publisher.rating',$property))->assertOk();
 }
}
