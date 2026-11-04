<?php

namespace App\Services\Ads;

use InvalidArgumentException;

class RevenueSplit {
    /**
     * All amounts are in integer minor currency units. This is a preliminary
     * commercial split BEFORE taxes, provider charges and refunds.
     */
    public function calculate(int $grossMinor,int $publisherPercent):array {
        if($grossMinor<1 || $publisherPercent<1 || $publisherPercent>99) {
            throw new InvalidArgumentException('Invalid amount or publisher share.');
        }
        $publisher=intdiv($grossMinor*$publisherPercent+50,100);
        return [
            'gross_minor'=>$grossMinor,
            'publisher_minor'=>$publisher,
            'platform_minor'=>$grossMinor-$publisher,
            'publisher_share_percent'=>$publisherPercent,
        ];
    }
}