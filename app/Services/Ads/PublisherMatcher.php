<?php

namespace App\Services\Ads;

use App\Models\AdCampaign;
use App\Models\PublisherAdPackage;
use Illuminate\Support\Collection;

class PublisherMatcher {
    /**
     * Explainable initial relevance ranking, not an estimated reach or sales prediction.
     * Unverified metrics do not earn performance points.
     */
    public function recommend(AdCampaign $campaign, int $limit=20): Collection {
        $packages=PublisherAdPackage::query()->where('is_active',true)
            ->where('moderation_status','approved')
            ->where('currency',$campaign->currency)
            ->where('price_minor','<=',$campaign->budget_minor)
            ->whereHas('property',fn($q)=>$q->where('verification_status','verified')
                ->where('country_code',$campaign->target_country))
            ->when($campaign->placement_type,fn($q)=>$q->where('placement_type',$campaign->placement_type))
            ->with(['property.metricSnapshots'=>fn($q)=>$q->where('verification_status','verified')
                ->orderByDesc('observed_on')->limit(5)])
            ->limit(500)->get();

        return $packages->map(function($package) use ($campaign) {
            $property=$package->property;
            $reasons=[];
            $score=0;
            $categoryMatch=mb_strtolower(trim($property->category))===mb_strtolower(trim($campaign->target_category));
            if($categoryMatch) { $score+=35; $reasons[]='Matching content category'; }
            else { $reasons[]='Different category; review audience suitability'; }
            // Country filtering is mandatory. City points require an exact named-city match.
            if($campaign->target_city && $property->city &&
               mb_strtolower(trim($campaign->target_city))===mb_strtolower(trim($property->city))) {
                $score+=25; $reasons[]='Matching city';
            } else {
                $reasons[]='Local audience not confirmed';
            }
            $recent=$property->metricSnapshots->first();
            if($recent && $recent->observed_on && $recent->observed_on->gte(now()->subDays(90))) {
                $score+=10;
                $reasons[]='Recently reviewed audience evidence';
                if($recent->views !== null && $recent->views>0) { $score+=10; $reasons[]='Verified recent views available'; }
            } else {
                $reasons[]='Insufficient verified recent engagement data';
            }
            if($package->price_minor <= (int) floor($campaign->budget_minor / 2)) {
                $score+=10;
                $reasons[]='Price fits within half the campaign budget';
            }
            // Remaining potential score is deliberately withheld without verified historical ad outcomes.
            return ['package'=>$package,'score'=>$score,'reasons'=>$reasons,'metrics_verified'=>!!$recent];
        })->sortByDesc('score')->take($limit)->values();
    }
}