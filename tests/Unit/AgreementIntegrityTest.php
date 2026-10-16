<?php
namespace Tests\Unit;
use App\Services\Agreements\AgreementIntegrity;
use PHPUnit\Framework\TestCase;
class AgreementIntegrityTest extends TestCase {
 public function test_json_object_order_does_not_change_digest():void {
  $a=['booking_id'=>'id1','price_minor'=>100,'nested'=>['z'=>1,'a'=>2]];
  $b=['nested'=>['a'=>2,'z'=>1],'price_minor'=>100,'booking_id'=>'id1'];
  $this->assertSame(AgreementIntegrity::digest($a),AgreementIntegrity::digest($b));
  $this->assertTrue(AgreementIntegrity::verify($b,AgreementIntegrity::digest($a)));
 }
 public function test_old_agreement_digest_remains_verifiable_after_key_reordering():void {
  $original=['version'=>1,'booking_id'=>'id1','customer_id'=>3,'creator_profile_id'=>'id2','brief'=>'demo','price_minor'=>100,'fee_minor'=>15,'currency'=>'EGP','payment_enabled'=>false];
  $oldHash=hash('sha256',json_encode($original,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
  ksort($original);
  $this->assertTrue(AgreementIntegrity::verify($original,$oldHash));
  $original['price_minor']=101;
  $this->assertFalse(AgreementIntegrity::verify($original,$oldHash));
 }
}
