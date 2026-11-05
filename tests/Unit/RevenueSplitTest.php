<?php

namespace Tests\Unit;

use App\Services\Ads\RevenueSplit;
use PHPUnit\Framework\TestCase;

class RevenueSplitTest extends TestCase {
    public function test_normal_seventy_thirty_split():void {
        $split=(new RevenueSplit())->calculate(10000,70);
        $this->assertSame(7000,$split['publisher_minor']);
        $this->assertSame(3000,$split['platform_minor']);
        $this->assertSame($split['gross_minor'],$split['publisher_minor']+$split['platform_minor']);
    }

    public function test_founder_eighty_twenty_split():void {
        $split=(new RevenueSplit())->calculate(9999,80);
        $this->assertSame(7999,$split['publisher_minor']);
        $this->assertSame(2000,$split['platform_minor']);
    }

    public function test_rounding_never_creates_or_loses_minor_units():void {
        $calculator=new RevenueSplit();
        foreach([1,2,3,5,7,99,101,10000001] as $gross) {
            foreach([70,80] as $rate) {
                $parts=$calculator->calculate($gross,$rate);
                $this->assertSame($gross,$parts['publisher_minor']+$parts['platform_minor']);
            }
        }
    }

    public function test_invalid_amount_is_rejected():void {
        $this->expectException(\InvalidArgumentException::class);
        (new RevenueSplit())->calculate(0,70);
    }

    public function test_invalid_share_is_rejected():void {
        $this->expectException(\InvalidArgumentException::class);
        (new RevenueSplit())->calculate(5000,100);
    }
}
