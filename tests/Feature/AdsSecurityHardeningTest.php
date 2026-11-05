<?php

namespace Tests\Feature;

use App\Models\PublisherProperty;
use App\Models\PublisherPromotion;
use App\Models\PublisherReferral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdsSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_email_is_normalized_before_uniqueness_check(): void
    {
        User::factory()->create(['email' => 'account@example.test']);
        $this->post(route('register.submit'), [
            'name' => 'Second Account',
            'email' => '  ACCOUNT@EXAMPLE.TEST  ',
            'password' => 'StrongPassword987',
            'password_confirmation' => 'StrongPassword987',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_admin_can_reject_property_without_ownership_evidence(): void
    {
        config()->set('vibsies_ads.enabled', true);
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $property = PublisherProperty::create([
            'user_id' => $owner->id,
            'platform' => 'facebook',
            'property_type' => 'group',
            'display_name' => 'Unverified Group',
            'profile_url' => 'https://www.facebook.com/groups/test',
            'category' => 'Community',
            'country_code' => 'EG',
            'verification_token' => 'REJECTNOEVIDENCE',
        ]);

        $this->actingAs($admin)->post(route('ads.admin.verify', $property), [
            'decision' => 'rejected',
            'note' => 'Missing proof of ownership from applicant',
        ])->assertRedirect();

        $this->assertDatabaseHas('publisher_properties', [
            'id' => $property->id,
            'verification_status' => 'rejected',
        ]);
        $this->assertDatabaseCount('publisher_promotions', 0);
    }

    public function test_approved_owner_cannot_refer_themselves_with_different_email_case(): void
    {
        config()->set('vibsies_ads.enabled', true);
        $publisher = User::factory()->create(['email' => 'publisher@example.test']);
        $property = PublisherProperty::create([
            'user_id' => $publisher->id,
            'platform' => 'facebook',
            'property_type' => 'page',
            'display_name' => 'Publisher',
            'profile_url' => 'https://www.facebook.com/publisher',
            'category' => 'Community',
            'country_code' => 'EG',
            'verification_token' => 'SELFREFERRAL1234',
        ]);
        $property->forceFill(['verification_status' => 'verified'])->save();
        $promotion = PublisherPromotion::create([
            'publisher_property_id' => $property->id,
            'referral_code' => 'VIBSIESABCDE',
            'post_url' => 'https://www.facebook.com/publisher/posts/123',
            'post_format' => 'post',
            'disclosure_confirmed' => true,
        ]);
        $promotion->forceFill(['status' => 'approved'])->save();

        // The uniqueness validator must reject before a referral can be attributed.
        $this->get(route('ads.referral.visit', $promotion->referral_code))
            ->assertRedirect(route('register'));
        $this->post(route('register.submit'), [
            'name' => 'Same Publisher',
            'email' => 'PUBLISHER@EXAMPLE.TEST',
            'password' => 'StrongPassword987',
            'password_confirmation' => 'StrongPassword987',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('publisher_referrals', 0);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_another_user_cannot_submit_promotion_for_a_foreign_property(): void
    {
        config()->set('vibsies_ads.enabled', true);
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $property = PublisherProperty::create([
            'user_id' => $owner->id,
            'platform' => 'facebook',
            'property_type' => 'page',
            'display_name' => 'Private Property',
            'profile_url' => 'https://www.facebook.com/ownpage',
            'category' => 'Community',
            'country_code' => 'EG',
            'verification_token' => 'FOREIGNPROPERTY12',
        ]);
        $property->forceFill(['verification_status'=>'verified'])->save();
        $this->actingAs($outsider)->post(route('ads.publisher.promotion', $property), [
            'post_url' => 'https://www.facebook.com/ownpage/posts/456',
            'post_format' => 'post',
            'disclosure_confirmed' => '1',
        ])->assertForbidden();
        $this->assertDatabaseCount('publisher_promotions', 0);
    }
}
