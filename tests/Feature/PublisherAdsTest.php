<?php

namespace Tests\Feature;

use App\Models\PublisherProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublisherAdsTest extends TestCase
{
    use RefreshDatabase;

    public function test_publisher_module_is_disabled_by_default(): void
    {
        config()->set('vibsies_ads.enabled', false);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('ads.publisher.index'))->assertNotFound();
    }

    public function test_verified_property_is_not_required_to_register(): void
    {
        config()->set('vibsies_ads.enabled', true);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('ads.publisher.store'), [
            'platform' => 'facebook',
            'property_type' => 'page',
            'display_name' => 'New Cairo Community',
            'profile_url' => 'https://www.facebook.com/example',
            'category' => 'Local community',
            'country_code' => 'EG',
            'city' => 'Cairo',
            'reported_followers' => 39000,
            'ownership_evidence_note' => 'Admin can verify page control manually.',
        ])->assertRedirect(route('ads.publisher.index'));

        $this->assertDatabaseHas('publisher_properties', [
            'user_id' => $user->id,
            'display_name' => 'New Cairo Community',
            'verification_status' => 'pending',
        ]);
    }

    public function test_promotion_cannot_be_submitted_before_property_verification(): void
    {
        config()->set('vibsies_ads.enabled', true);
        $owner = User::factory()->create();
        $property = PublisherProperty::create([
            'user_id' => $owner->id,
            'platform' => 'facebook',
            'property_type' => 'page',
            'display_name' => 'Example',
            'profile_url' => 'https://www.facebook.com/example',
            'category' => 'Community',
            'country_code' => 'EG',
            'verification_token' => 'SAMPLECODE123456',
        ]);

        $this->actingAs($owner)->post(route('ads.publisher.promotion', $property), [
            'post_url' => 'https://www.facebook.com/example/posts/123',
            'post_format' => 'post',
            'disclosure_confirmed' => '1',
        ])->assertStatus(409);

        $this->assertDatabaseCount('publisher_promotions', 0);
    }

    public function test_other_publishers_cannot_submit_promotions_for_a_property(): void
    {
        config()->set('vibsies_ads.enabled', true);
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $property = PublisherProperty::create([
            'user_id' => $owner->id,
            'platform' => 'facebook',
            'property_type' => 'page',
            'display_name' => 'Example',
            'profile_url' => 'https://www.facebook.com/example',
            'category' => 'Community',
            'country_code' => 'EG',
            'verification_token' => 'SAMPLECODE123457',
        ]);
        $property->forceFill(['verification_status' => 'verified'])->save();

        $this->actingAs($outsider)->post(route('ads.publisher.promotion', $property), [
            'post_url' => 'https://www.facebook.com/example/posts/123',
            'post_format' => 'post',
            'disclosure_confirmed' => '1',
        ])->assertForbidden();
    }
    public function test_admin_must_document_verification_and_cannot_repeat_it(): void
    {
        config()->set('vibsies_ads.enabled', true);
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $property = PublisherProperty::create([
            'user_id' => $owner->id,
            'platform' => 'facebook',
            'property_type' => 'page',
            'display_name' => 'Example',
            'profile_url' => 'https://www.facebook.com/example',
            'category' => 'Community',
            'country_code' => 'EG',
            'verification_token' => 'DOCUMENTEDREVIEW1',
            'ownership_evidence_note' => 'Proof of account ownership reviewed manually.',
        ]);

        $this->actingAs($admin)->post(route('ads.admin.verify', $property), [
            'decision' => 'verified',
        ])->assertSessionHasErrors('note');

        $this->actingAs($admin)->post(route('ads.admin.verify', $property), [
            'decision' => 'verified',
            'note' => 'Checked evidence supplied by the owner.',
        ])->assertRedirect();

        $this->assertDatabaseHas('publisher_properties', [
            'id' => $property->id,
            'verification_status' => 'verified',
        ]);

        $this->actingAs($admin)->post(route('ads.admin.verify', $property), [
            'decision' => 'verified',
            'note' => 'Repeat approval should be blocked.',
        ])->assertStatus(409);
    }

}
