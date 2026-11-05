<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\PublisherProperty;
class PublisherScorecardPrivacyTest extends TestCase {
 use RefreshDatabase;
 public function test_public_rating_is_hidden_for_unrated_publisher():void {
  config()->set('vibsies_ads.enabled',true);
  config()->set('vibsies_ads.public_ratings_enabled',true);
  $owner=User::factory()->create();
  $property=PublisherProperty::create(['user_id'=>$owner->id,'platform'=>'facebook','property_type'=>'page','display_name'=>'Example','profile_url'=>'https://www.facebook.com/example','category'=>'Community','country_code'=>'EG','verification_token'=>'CODE1234567890']);
  $property->forceFill(['verification_status'=>'verified'])->save();
  $this->get(route('seo.publisher.rating',$property))->assertNotFound();
 }
}
