<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdsLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_arabic_advertiser_dashboard_renders_rtl_labels_and_stylesheet(): void
    {
        config()->set('vibsies_ads.enabled', true);
        $user=User::factory()->create();

        $this->withSession(['locale'=>'ar'])->actingAs($user)
            ->get(route('ads.advertiser.index'))
            ->assertOk()
            ->assertSee('lang="ar"',false)
            ->assertSee('dir="rtl"',false)
            ->assertSee('حملة جديدة')
            ->assertSee('css/ads-mobile.css');
    }

    public function test_english_advertiser_dashboard_renders_ltr_and_language_switcher(): void
    {
        config()->set('vibsies_ads.enabled', true);
        $this->actingAs(User::factory()->create())
            ->get(route('ads.advertiser.index'))
            ->assertOk()
            ->assertSee('dir="ltr"',false)
            ->assertSee('Save draft and find publishers')
            ->assertSee('العربية');
    }

    public function test_arabic_publisher_dashboard_renders_on_mobile_layout(): void
    {
        config()->set('vibsies_ads.enabled',true);
        $this->withSession(['locale'=>'ar'])->actingAs(User::factory()->create())
            ->get(route('ads.publisher.index'))
            ->assertOk()
            ->assertSee('حسابات النشر')
            ->assertSee('name="viewport"',false)
            ->assertSee('dir="rtl"',false);
    }

    public function test_arabic_publisher_offers_page_renders_without_offers(): void
    {
        config()->set('vibsies_ads.enabled',true);
        $this->withSession(['locale'=>'ar'])->actingAs(User::factory()->create())
            ->get(route('ads.publisher.offers'))
            ->assertOk()
            ->assertSee('عروض الإعلانات')
            ->assertSee('dir="rtl"',false);
    }

    public function test_arabic_admin_dashboard_is_restricted_and_translated(): void
    {
        config()->set('vibsies_ads.enabled',true);
        $user=User::factory()->create();
        $this->actingAs($user)->get(route('ads.admin.index'))->assertForbidden();

        $admin=User::factory()->create();
        $admin->forceFill(['is_admin'=>true])->save();
        $this->withSession(['locale'=>'ar'])->actingAs($admin)
            ->get(route('ads.admin.index'))
            ->assertOk()
            ->assertSee('قائمة مراجعات فيبسيز')
            ->assertSee('dir="rtl"',false);
    }

    public function test_language_switch_returns_to_same_origin_page_only(): void
    {
        $this->withHeaders(['referer'=>'http://localhost/ads/advertiser'])
            ->post(route('locale.switch'),['locale'=>'ar'])
            ->assertRedirect('http://localhost/ads/advertiser');

        $this->withHeaders(['referer'=>'https://external.example/unsafe'])
            ->post(route('locale.switch'),['locale'=>'en'])
            ->assertRedirect(route('home'));
    }
}
