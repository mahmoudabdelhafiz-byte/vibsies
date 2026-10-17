<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class AdminLocalizationTest extends TestCase {
 use RefreshDatabase;
 public function test_arabic_admin_dashboard_renders_rtl_labels():void {
  $admin=User::factory()->create();
  $admin->forceFill(['is_admin'=>true])->save();
  $this->actingAs($admin)->withSession(['locale'=>'ar'])->get('/admin')
   ->assertOk()->assertSee('dir="rtl"',false)->assertSee('الإدارة')->assertSee('طلبات التحقق المعلقة');
 }
 public function test_non_admin_cannot_access_localized_admin_dashboard():void {
  $this->actingAs(User::factory()->create())->withSession(['locale'=>'ar'])->get('/admin')->assertForbidden();
 }
}
