<?php

namespace Tests\Feature;

use App\Models\PublisherProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublisherOwnershipChallengeTest extends TestCase
{
    use RefreshDatabase;

    private function property(User $owner, bool $expired=false): PublisherProperty {
        return PublisherProperty::create([
            'user_id'=>$owner->id,
            'platform'=>'facebook',
            'property_type'=>'page',
            'display_name'=>'Community',
            'profile_url'=>'https://www.facebook.com/example',
            'category'=>'Local community',
            'country_code'=>'EG',
            'verification_token'=>'OWNERCHALLENGE01',
            'verification_expires_at'=>$expired?now()->subDay():now()->addDays(7),
        ]);
    }

    public function test_owner_submits_evidence_without_promoting_vibsies(): void {
        config()->set('vibsies_ads.enabled',true);
        $owner=User::factory()->create();
        $property=$this->property($owner);
        $this->actingAs($owner)->post(route('ads.publisher.evidence',$property),[
            'ownership_evidence_url'=>'https://www.facebook.com/example/posts/123',
            'ownership_evidence_note'=>'Verification challenge appears on the linked social post.',
        ])->assertRedirect(route('ads.publisher.index'));
        $this->assertNotNull($property->fresh()->challenge_submitted_at);
        $this->assertDatabaseHas('publisher_verification_events',[
            'publisher_property_id'=>$property->id,'action'=>'evidence_submitted',
        ]);
        $this->assertDatabaseCount('publisher_promotions',0);
    }

    public function test_other_user_cannot_submit_challenge_evidence(): void {
        config()->set('vibsies_ads.enabled',true);
        $property=$this->property(User::factory()->create());
        $this->actingAs(User::factory()->create())->post(route('ads.publisher.evidence',$property),[
            'ownership_evidence_url'=>'https://www.facebook.com/example/posts/123',
            'ownership_evidence_note'=>'Trying to modify someone else account.',
        ])->assertForbidden();
    }

    public function test_expired_challenge_blocks_evidence_submission(): void {
        config()->set('vibsies_ads.enabled',true);
        $owner=User::factory()->create();
        $property=$this->property($owner,true);
        $this->actingAs($owner)->post(route('ads.publisher.evidence',$property),[
            'ownership_evidence_url'=>'https://www.facebook.com/example/posts/123',
            'ownership_evidence_note'=>'This challenge has already expired.',
        ])->assertStatus(409);
        $this->assertNull($property->fresh()->challenge_submitted_at);
    }

    public function test_admin_cannot_approve_without_attestation_and_owner_evidence(): void {
        config()->set('vibsies_ads.enabled',true);
        $owner=User::factory()->create();
        $admin=User::factory()->create();
        $admin->forceFill(['is_admin'=>true])->save();
        $property=$this->property($owner);
        $payload=['decision'=>'verified','note'=>'I checked the public challenge code','challenge_checked'=>'1'];
        $this->actingAs($admin)->post(route('ads.admin.verify',$property),$payload)->assertStatus(409);
        $this->actingAs($owner)->post(route('ads.publisher.evidence',$property),[
            'ownership_evidence_url'=>'https://www.facebook.com/example/posts/123',
            'ownership_evidence_note'=>'The exact challenge code is shown on my public page.',
        ])->assertRedirect();
        $this->actingAs($admin)->post(route('ads.admin.verify',$property),[
            'decision'=>'verified','note'=>'The administrator independently verified the code',
        ])->assertStatus(422);
        $this->actingAs($admin)->post(route('ads.admin.verify',$property),$payload)->assertRedirect();
        $this->assertDatabaseHas('publisher_verification_events',[
            'publisher_property_id'=>$property->id,'action'=>'reviewed','decision'=>'verified','actor_id'=>$admin->id,
        ]);
        $this->assertSame($admin->id,$property->fresh()->verified_by_user_id);
    }

    public function test_evidence_link_must_use_same_social_platform(): void {
        config()->set('vibsies_ads.enabled',true);
        $owner=User::factory()->create();
        $property=$this->property($owner);
        $this->actingAs($owner)->post(route('ads.publisher.evidence',$property),[
            'ownership_evidence_url'=>'https://www.instagram.com/example',
            'ownership_evidence_note'=>'Incorrect social network for this property.',
        ])->assertSessionHasErrors('profile_url');
    }
    public function test_duplicate_publisher_url_variant_cannot_be_claimed_twice(): void {
        config()->set('vibsies_ads.enabled',true);
        $a=User::factory()->create();
        $b=User::factory()->create();
        $details=[
            'platform'=>'facebook','property_type'=>'page','display_name'=>'Community',
            'profile_url'=>'https://www.facebook.com/example/',
            'category'=>'Local','country_code'=>'EG',
        ];
        $this->actingAs($a)->post(route('ads.publisher.store'),$details)
            ->assertRedirect(route('ads.publisher.index'));
        $details['profile_url']='https://m.facebook.com/example';
        $this->actingAs($b)->post(route('ads.publisher.store'),$details)
            ->assertSessionHasErrors('profile_url');
        $this->assertDatabaseCount('publisher_properties',1);
    }

}
