<?php

namespace Tests\Feature;

use App\Models\PublisherPromotion;
use App\Models\PublisherProperty;
use App\Models\PublisherReferral;
use App\Models\PublisherRewardEntitlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublisherReferralTest extends TestCase {
    use RefreshDatabase;

    private function promotion(string $status='approved'): PublisherPromotion {
        $owner=User::factory()->create();
        $property=PublisherProperty::create([
            'user_id'=>$owner->id,'platform'=>'facebook','property_type'=>'page',
            'display_name'=>'New Cairo','profile_url'=>'https://www.facebook.com/newcairo',
            'category'=>'Community','country_code'=>'EG','verification_token'=>'REFERRALCODE1234',
        ]);
        $property->forceFill(['verification_status'=>'verified'])->save();
        $promotion=PublisherPromotion::create([
            'publisher_property_id'=>$property->id,
            'referral_code'=>'VIBSIES12345',
            'post_url'=>'https://www.facebook.com/newcairo/posts/123',
            'post_format'=>'post','disclosure_confirmed'=>true,
        ]);
        $promotion->forceFill(['status'=>$status])->save();
        return $promotion;
    }

    public function test_pending_promotion_link_does_not_attribute_visitors(): void {
        config()->set('vibsies_ads.enabled',true);
        $promotion=$this->promotion('pending');
        $this->get(route('ads.referral.visit',$promotion->referral_code))->assertNotFound();
    }

    public function test_approved_promotion_link_sends_guest_to_registration(): void {
        config()->set('vibsies_ads.enabled',true);
        $promotion=$this->promotion();
        $this->get(route('ads.referral.visit',$promotion->referral_code))
            ->assertRedirect(route('register'));
        $this->assertEquals($promotion->id,session('vibsies_referral.promotion_id'));
    }

    public function test_referral_is_assigned_only_once_to_new_registration(): void {
        config()->set('vibsies_ads.enabled',true);
        $promotion=$this->promotion();
        $this->get(route('ads.referral.visit',$promotion->referral_code));
        $this->post(route('register.submit'),[
            'name'=>'New Customer','email'=>'newcustomer@example.test',
            'password'=>'StrongPass987','password_confirmation'=>'StrongPass987',
        ])->assertRedirect(route('dashboard'));
        $this->assertSame(1,PublisherReferral::where('publisher_promotion_id',$promotion->id)->count());
        $this->assertSame('registered',PublisherReferral::first()->status);
        $this->assertNull(session('vibsies_referral'));
    }

    public function test_expired_session_referral_is_not_recorded(): void {
        config()->set('vibsies_ads.enabled',true);
        $promotion=$this->promotion();
        $this->withSession(['vibsies_referral'=>[
            'promotion_id'=>$promotion->id,'captured_at'=>now()->subDays(8)->timestamp,
        ]])->post(route('register.submit'),[
            'name'=>'New Customer','email'=>'newcustomer@example.test',
            'password'=>'StrongPass987','password_confirmation'=>'StrongPass987',
        ])->assertRedirect(route('dashboard'));
        $this->assertDatabaseCount('publisher_referrals',0);
    }

    public function test_approval_creates_nonfinancial_benefit_at_most_once(): void {
        config()->set('vibsies_ads.enabled',true);
        $promotion=$this->promotion('pending');
        $admin=User::factory()->create();
        $admin->forceFill(['is_admin'=>true])->save();
        $this->actingAs($admin)->post(route('ads.admin.promotion',$promotion),[
            'decision'=>'approved',
        ])->assertRedirect();
        $this->assertDatabaseHas('publisher_reward_entitlements',[
            'publisher_property_id'=>$promotion->publisher_property_id,
            'publisher_share_percent'=>80,
            'placement_limit'=>3,
            'placements_used'=>0,
            'status'=>'eligible',
        ]);
        $this->actingAs($admin)->post(route('ads.admin.promotion',$promotion),[
            'decision'=>'approved',
        ])->assertStatus(409);
        $this->assertSame(1,PublisherRewardEntitlement::count());
    }

    public function test_referral_is_feature_flagged_off(): void {
        config()->set('vibsies_ads.enabled',false);
        $promotion=$this->promotion();
        $this->get(route('ads.referral.visit',$promotion->referral_code))->assertNotFound();
    }
}
