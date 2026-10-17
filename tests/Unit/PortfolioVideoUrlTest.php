<?php
namespace Tests\Unit;
use App\Services\Creators\PortfolioVideoUrl;
use PHPUnit\Framework\TestCase;
class PortfolioVideoUrlTest extends TestCase {
 public function test_supported_watch_links():void {
  $this->assertTrue(PortfolioVideoUrl::valid('https://www.youtube.com/watch?v=AbCdEf123_-'));
  $this->assertTrue(PortfolioVideoUrl::valid('https://youtu.be/AbCdEf123_-'));
  $this->assertTrue(PortfolioVideoUrl::valid('https://vimeo.com/123456789'));
 }
 public function test_misleading_and_non_video_links_are_rejected():void {
  foreach(['https://youtube.com.evil.test/watch?v=AbCdEf123_-','http://youtu.be/AbCdEf123_-',
   'https://youtube.com/@creator','https://youtu.be/not-a-video',
   'https://vimeo.com/channels/staffpicks','https://user:pass@vimeo.com/123456789',
   'https://vimeo.com:8443/123456789','https://youtu.be/AbCdEf123_-#section'] as $url)
   $this->assertFalse(PortfolioVideoUrl::valid($url),$url);
 }
}
