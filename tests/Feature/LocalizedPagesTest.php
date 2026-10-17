<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class LocalizedPagesTest extends TestCase {
 use RefreshDatabase;
 public function test_arabic_guest_login_and_register_use_rtl_layout():void {
  $this->withSession(['locale'=>'ar'])->get('/login')->assertOk()->assertSee('dir="rtl"',false)->assertSee('تسجيل الدخول');
  $this->withSession(['locale'=>'ar'])->get('/register')->assertOk()->assertSee('إنشاء حساب');
 }
 public function test_authenticated_pages_use_arabic_labels():void {
  $user=User::factory()->create();
  $this->actingAs($user)->withSession(['locale'=>'ar'])->get('/dashboard')->assertOk()->assertSee('حجوزاتي');
  $this->actingAs($user)->withSession(['locale'=>'ar'])->get('/creator')->assertOk()->assertSee('استوديو صانع المحتوى');
  $this->actingAs($user)->withSession(['locale'=>'ar'])->get('/business')->assertOk()->assertSee('حسابات الشركات');
 }
}
