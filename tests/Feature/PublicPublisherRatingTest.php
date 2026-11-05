<?php
namespace Tests\Feature;
use Tests\TestCase;
class PublicPublisherRatingTest extends TestCase {
 public function test_rating_visibility_defaults_off():void {
  $this->assertFalse(config('vibsies_ads.public_ratings_enabled'));
 }
}
