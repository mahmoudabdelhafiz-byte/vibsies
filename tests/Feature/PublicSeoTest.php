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
        $creator=CreatorProfile::factory()->create();
        $creator->forceFill(['status'=>'approved'])->save();
        $package=CreatorPackage::factory()->create(['creator_profile_id'=>$creator->id]);
        $package->forceFill(['is_active'=>true,'moderation_status'=>'approved'])->save();
        $this->get(route('seo.sitemap'))->assertOk()
            ->assertHeader('Content-Type','application/xml; charset=UTF-8')
            ->assertSee(route('creators.show',$creator->slug),false)
            ->assertDontSee('/ads/advertiser',false)
            ->assertDontSee('/r/',false);
    }

    public function test_sitemap_excludes_unapproved_profiles():void {
        $creator=CreatorProfile::factory()->create();
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
}
