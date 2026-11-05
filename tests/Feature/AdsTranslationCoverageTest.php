<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdsTranslationCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_arabic_and_english_ads_catalogs_have_the_same_keys(): void
    {
        $en = require base_path('lang/en/ads.php');
        $ar = require base_path('lang/ar/ads.php');
        $enKeys=array_keys($en);
        $arKeys=array_keys($ar);
        sort($enKeys);
        sort($arKeys);
        $this->assertSame($enKeys,$arKeys);
        foreach ($en as $key => $english) {
            $this->assertNotSame('', trim($english), $key);
            $this->assertNotSame('', trim($ar[$key]), $key);
        }
    }

    public function test_all_ads_view_translation_keys_exist_in_both_locales(): void
    {
        $en = require base_path('lang/en/ads.php');
        $ar = require base_path('lang/ar/ads.php');
        foreach ([
            'advertiser-ads/index.blade.php',
            'advertiser-ads/show.blade.php',
            'publisher-ads/index.blade.php',
            'publisher-ads/offers.blade.php',
            'publisher-ads/admin.blade.php',
        ] as $path) {
            $blade=file_get_contents(resource_path('views/'.$path));
            preg_match_all("/__\\('ads\\.([a-z_]+)'\\)/", $blade, $matches);
            foreach ($matches[1] as $key) {
                $this->assertArrayHasKey($key,$en,$path);
                $this->assertArrayHasKey($key,$ar,$path);
            }
            $this->assertStringContainsString("dir=\"{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}\"",$blade);
            $this->assertStringContainsString("ads-mobile.css",$blade);
        }
    }

    public function test_arabic_advertiser_has_localized_category_and_paid_placement_disclaimer(): void
    {
        config()->set('vibsies_ads.enabled',true);
        $user=User::factory()->create();
        $this->withSession(['locale'=>'ar'])->actingAs($user)
            ->get(route('ads.advertiser.index'))
            ->assertOk()
            ->assertSee('تصنيف المحتوى')
            ->assertSee('لا يتم تحصيل أي مبالغ');
    }

    public function test_arabic_publisher_explains_optional_promotion_and_data_limits(): void
    {
        config()->set('vibsies_ads.enabled',true);
        $user=User::factory()->create();
        $this->withSession(['locale'=>'ar'])->actingAs($user)
            ->get(route('ads.publisher.index'))
            ->assertOk()
            ->assertSee('الترويج لفيبسيز اختياري')
            ->assertSee('أرقام الجمهور');
    }
}
