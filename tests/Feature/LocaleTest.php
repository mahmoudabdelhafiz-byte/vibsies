<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class LocaleTest extends TestCase {
 use RefreshDatabase;
 public function test_default_locale_is_english():void {$this->get('/')->assertOk()->assertSee('lang="en"',false);}
 public function test_switch_to_arabic_enables_rtl():void {
  $this->post('/locale',['locale'=>'ar'])->assertRedirect('/');
  $this->get('/')->assertOk()->assertSee('lang="ar"',false)->assertSee('dir="rtl"',false)->assertSee('اكتشف صُنّاع المحتوى');
 }
 public function test_invalid_locale_cannot_be_set():void {$this->post('/locale',['locale'=>'de'])->assertSessionHasErrors('locale');}
}