<?php

namespace Tests\Feature;

use App\Models\PublisherProperty;
use App\Models\PublisherPromotion;
use App\Models\PublisherReferral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublisherTrustTest extends TestCase {
    use RefreshDatabase;

    private function pendingProperty(User $owner): PublisherProperty {
        return PublisherProperty::create([
            'user_id'=>$owner->id, 'platform'=>'facebook','property_type'=>'page',
            'display_name'=>'Publisher','profile_url'=>'https://www.facebook.com/publisher',
            'category'=>'Community','country_code'=>'EG',
            'verification_token'=>'INITIALCODE12345',
            'verification_expires_at'=>now()->subDay(),
        ]);
    }

    public function test_owner_renews_expired_challenge_and_clears_stale_evidence(): void {
        config()->set('vibsies_ads.enabled',true);
        $owner=User::factory()->create();
        $property=$this->pendingProperty($owner);
        $property->forceFill([
            'ownership_evidence_url'=>'https://www.facebook.com/publisher/posts/old',
            'ownership_evidence_note'=>'Old challenge proof',
            'challenge_submitted_at'=>now()->subDays(8),
        ])->save();
        $old=$property->verification_token;
        $this->actingAs($owner)->post(route('ads.publisher.challenge.renew',$property))
            ->assertRedirect(route('ads.publisher.index'));
        $new=$property->fresh();
        $this->assertNotSame($old,$new->verification_token);
        $this->assertSame(1,$new->challenge_renewal_count);
        $this->assertNull($new->ownership_evidence_url);
        $this->assertNull($new->challenge_submitted_at);
        $this->assertTrue($new->verification_expires_at->isFuture());
        $this->assertDatabaseHas('publisher_verification_events',[
            'publisher_property_id'=>$property->id,'action'=>'challenge_renewed',
        ]);
    }

    public function test_renewal_requires_expiry_and_owner(): void {
        config()->set('vibsies_ads.enabled',true);
        $owner=User::factory()->create();
        $property=$this->pendingProperty($owner);
        $this->actingAs(User::factory()->create())->post(route('ads.publisher.challenge.renew',$property))
            ->assertForbidden();
        $property->forceFill(['verification_expires_at'=>now()->addDay()])->save();
        $this->actingAs($owner)->post(route('ads.publisher.challenge.renew',$property))
            ->assertStatus(409);
    }

    public function test_renewal_is_limited_to_two(): void {
        config()->set('vibsies_ads.enabled',true);
        $owner=User::factory()->create();
        $property=$this->pendingProperty($owner);
        $property->forceFill(['challenge_renewal_count'=>2])->save();
        $this->actingAs($owner)->post(route('ads.publisher.challenge.renew',$property))
            ->assertStatus(409);
    }

    private function referral(): PublisherReferral {
        $owner=User::factory()->create();
        $property=$this->pendingProperty($owner);
        $property->forceFill(['verification_status'=>'verified'])->save();
        $promotion=PublisherPromotion::create([
            'publisher_property_id'=>$property->id,
            'referral_code'=>'REVIEWCODE123',
            'post_format'=>'post',
            'post_url'=>'https://www.facebook.com/publisher/posts/1',
            'disclosure_confirmed'=>true,
        ]);
        $promotion->forceFill(['status'=>'approved'])->save();
        return PublisherReferral::create([
            'publisher_promotion_id'=>$promotion->id,
            'referred_user_id'=>User::factory()->create()->id,
            'status'=>'registered','registered_at'=>now(),
        ]);
    }

    public function test_only_admin_can_flag_referral_without_qualifying_rewards(): void {
        config()->set('vibsies_ads.enabled',true);
        $referral=$this->referral();
        $other=User::factory()->create();
        $this->actingAs($other)->post(route('ads.admin.referral.review',$referral),[
            'decision'=>'flagged','note'=>'Suspicious duplicate registration activity',
        ])->assertForbidden();
        $admin=User::factory()->create();
        $admin->forceFill(['is_admin'=>true])->save();
        $this->actingAs($admin)->post(route('ads.admin.referral.review',$referral),[
            'decision'=>'flagged','note'=>'Suspected abuse; pending further investigation',
        ])->assertRedirect();
        $this->assertDatabaseHas('publisher_referrals',[
            'id'=>$referral->id,'review_status'=>'flagged','status'=>'registered',
        ]);
        $this->assertDatabaseHas('publisher_referral_review_events',[
            'publisher_referral_id'=>$referral->id,'decision'=>'flagged','actor_id'=>$admin->id,
        ]);
        $this->assertDatabaseCount('ad_ledger_batches',0);
    }

    public function test_repeated_referral_review_is_blocked(): void {
        config()->set('vibsies_ads.enabled',true);
        $referral=$this->referral();
        $admin=User::factory()->create();
        $admin->forceFill(['is_admin'=>true])->save();
        $payload=['decision'=>'reviewed','note'=>'Checked publisher and registration evidence'];
        $this->actingAs($admin)->post(route('ads.admin.referral.review',$referral),$payload)->assertRedirect();
        $this->actingAs($admin)->post(route('ads.admin.referral.review',$referral),$payload)->assertStatus(409);
        $this->assertDatabaseCount('publisher_referral_review_events',1);
        $this->assertDatabaseHas('publisher_referrals',['id'=>$referral->id,'status'=>'registered']);
    }
}
