<?php

namespace Tests\Feature;

use App\Models\PublisherProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublisherInventoryTest extends TestCase
{
    use RefreshDatabase;

    private function property(User $owner, string $status = 'verified'): PublisherProperty
    {
        $property = PublisherProperty::create([
            'user_id' => $owner->id,
            'platform' => 'facebook',
            'property_type' => 'page',
            'display_name' => 'New Cairo News',
            'profile_url' => 'https://www.facebook.com/newcairo',
            'category' => 'Local community',
            'country_code' => 'EG',
            'verification_token' => 'CODE123456789012',
        ]);
        $property->forceFill(['verification_status' => $status])->save();
        return $property;
    }

    public function test_package_is_pending_and_cannot_be_sold_without_admin_review(): void
    {
        config()->set('vibsies_ads.enabled',true);
        $owner=User::factory()->create();
        $property=$this->property($owner);
        $this->actingAs($owner)->post(route('ads.publisher.packages.store',$property),[
            'title'=>'Sponsored post',
            'placement_type'=>'feed_post',
            'price'=>'450.00',
            'currency'=>'EGP',
            'delivery_days'=>7,
        ])->assertRedirect(route('ads.publisher.index'));
        $this->assertDatabaseHas('publisher_ad_packages',[
            'publisher_property_id'=>$property->id,
            'price_minor'=>45000,
            'moderation_status'=>'pending',
            'is_active'=>false,
        ]);
    }

    public function test_another_user_cannot_modify_property_inventory(): void
    {
        config()->set('vibsies_ads.enabled',true);
        $property=$this->property(User::factory()->create());
        $this->actingAs(User::factory()->create())->post(route('ads.publisher.packages.store',$property),[
            'title'=>'Sponsored post','placement_type'=>'feed_post','price'=>500,
            'currency'=>'EGP','delivery_days'=>7,
        ])->assertForbidden();
    }

    public function test_group_only_accepts_group_placements(): void
    {
        config()->set('vibsies_ads.enabled',true);
        $owner=User::factory()->create();
        $property=$this->property($owner);
        $property->forceFill(['property_type'=>'group'])->save();
        $this->actingAs($owner)->post(route('ads.publisher.packages.store',$property),[
            'title'=>'Invalid story','placement_type'=>'story','price'=>500,
            'currency'=>'EGP','delivery_days'=>7,
        ])->assertSessionHasErrors('placement_type');
    }

    public function test_metrics_are_self_reported_not_verified(): void
    {
        config()->set('vibsies_ads.enabled',true);
        $owner=User::factory()->create();
        $property=$this->property($owner,'pending');
        $this->actingAs($owner)->post(route('ads.publisher.metrics.store',$property),[
            'observed_on'=>'2026-10-01','content_category'=>'Local news','views'=>8300,
            'reactions'=>26,'comments'=>3,'shares'=>3,
        ])->assertRedirect(route('ads.publisher.index'));
        $this->assertDatabaseHas('publisher_metric_snapshots',[
            'publisher_property_id'=>$property->id,'views'=>8300,
            'source_type'=>'self_reported','verification_status'=>'unverified',
        ]);
    }

    public function test_future_metric_date_is_rejected(): void
    {
        config()->set('vibsies_ads.enabled',true);
        $owner=User::factory()->create();
        $property=$this->property($owner);
        $this->actingAs($owner)->post(route('ads.publisher.metrics.store',$property),[
            'observed_on'=>now()->addDay()->toDateString(),'views'=>1000,
        ])->assertSessionHasErrors('observed_on');
    }

    public function test_feature_flag_blocks_inventory_routes(): void
    {
        config()->set('vibsies_ads.enabled',false);
        $owner=User::factory()->create();
        $property=$this->property($owner);
        $this->actingAs($owner)->post(route('ads.publisher.metrics.store',$property),[
            'observed_on'=>'2026-10-01','views'=>500,
        ])->assertNotFound();
    }
}
