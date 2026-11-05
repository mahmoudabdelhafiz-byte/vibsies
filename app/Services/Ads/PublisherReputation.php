<?php

namespace App\Services\Ads;

use App\Models\PublisherProperty;

class PublisherReputation
{
    /**
     * Reviewed feedback from completed offers, never raw follower counts or
     * self-reported engagement. Default unranked until 3 independent reviews.
     */
    public function summary(PublisherProperty $property): array {
        $reviews=$property->campaignReviews()
            ->where('moderation_status','approved')
            ->whereDoesntHave('appeal',fn($q)=>$q->whereIn('status',['pending','upheld']))
            ->whereHas('offer',fn($q)=>$q->whereIn('status',['confirmed','resolved_confirmed']))
            ->where('created_at','>=',now()->subMonths(12))
            ->get();
        $count=$reviews->count();
        $independent=$reviews->unique('advertiser_user_id')->count();
        $eligible=$count>=3 && $independent>=3;
        $averages=$reviews->groupBy('advertiser_user_id')->map(fn($group)=>$group->avg('overall_rating'));
        return [
            'status'=>$eligible?'rated':'unrated',
            'review_count'=>$count,
            'independent_advertisers'=>$independent,
            'average_overall'=>$eligible?round($averages->avg(),1):null,
            'last_review_at'=>$count?$reviews->max('created_at'):null,
            'basis'=>'approved advertiser reviews of completed placements; last 12 months',
        ];
    }
}