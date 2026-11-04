<?php

namespace Tests\Feature;

use App\Models\CreatorPackage;
use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_only_contains_public_approved_creator_profiles():void {
        $creator=CreatorProfile::create([
            'user_id'=>User::factory()->create()->id,
            'display_name'=>'Example Creator','slug'=>'example-creator',
            'category'=>'Lifestyle','country_code'=>'EG','status'=>'approved',
        ]);
        $creator->forceFill(['status'=>'approved'])->save();
        $package=CreatorPackage::create([
            'creator_profile_id'=>$creator->id,'title'=>'Video campaign',
            'description'=>'One promotional video','price_minor'=>10000,'currency'=>'EGP',
            'turnaround_days'=>7,'revision_limit'=>1,'is_active'=>true,
            'moderation_status'=>'approved',
        ]);
        $package->forceFill(['is_active'=>true,'moderation_status'=>'approved'])->save();
        $this->get(route('seo.sitemap'))->assertOk()
            ->assertHeader('Content-Type','application/xml; charset=UTF-8')
            ->assertSee(route('creators.show',$creator->slug),false)
            ->assertDontSee('/ads/advertiser',false)
            ->assertDontSee('/r/',false);
    }

    public function test_sitemap_excludes_unapproved_profiles():void {
        $creator=CreatorProfile::create([
            'user_id'=>User::factory()->create()->id,
            'display_name'=>'Unapproved Creator','slug'=>'unapproved-creator',
            'category'=>'Lifestyle','country_code'=>'EG','status'=>'pending',
        ]);
        $creator->forceFill(['status'=>'pending'])->save();
        $this->get(route('seo.sitemap'))->assertOk()
            ->assertDontSee(route('creators.show',$creator->slug),false);
    }

    public function test_robots_points_to_sitemap_and_disallows_private_areas():void {
        $this->get(route('seo.robots'))->assertOk()
            ->assertSee(route('seo.sitemap'),false)
            ->assertSee('Disallow: /ads/',false)
            ->assertSee('Disallow: /r/',false);
    }

    public function test_public_home_has_canonical_and_description():void {
        $this->get(route('home'))->assertOk()
            ->assertSee('rel="canonical"',false)
            ->assertSee('name="description"',false)
            ->assertSee('property="og:title"',false);
    }

    public function test_filtered_search_result_is_noindex_follow():void {
        $this->get(route('home').'?q=example')->assertOk()
            ->assertSee('name="robots" content="noindex,follow"',false);
    }
    public function test_publisher_guide_is_hidden_until_ads_available():void {
        config()->set('vibsies_ads.enabled',false);
        $this->get(route('seo.publishers'))->assertNotFound();
        config()->set('vibsies_ads.enabled',true);
        $this->get(route('seo.publishers'))->assertOk();
        $this->get(route('seo.sitemap'))->assertSee('advertise-with-publishers',false);
    }

    public function test_public_home_has_truthful_website_structured_data():void {
        $this->get(route('home'))->assertOk()
            ->assertSee('application/ld+json',false)
            ->assertSee('https://schema.org',false)
            ->assertDontSee('AggregateRating',false);
    }

    public function test_private_dashboard_does_not_publish_rating_structured_data():void {
        config()->set('vibsies_ads.enabled',true);
        $this->actingAs(\App\Models\User::factory()->create())
            ->get(route('ads.publisher.index'))->assertOk()
            ->assertDontSee('AggregateRating',false);
    }

}
