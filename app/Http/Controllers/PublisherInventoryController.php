<?php

namespace App\Http\Controllers;

use App\Models\PublisherAdPackage;
use App\Models\PublisherMetricSnapshot;
use App\Models\PublisherProperty;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublisherInventoryController extends Controller
{
    private function property(Request $request, PublisherProperty $property): PublisherProperty
    {
        abort_unless(config('vibsies_ads.enabled'), 404);
        abort_unless($property->user_id === $request->user()->id, 403);
        return $property;
    }

    public function storePackage(Request $request, PublisherProperty $property)
    {
        $property = $this->property($request, $property);
        abort_unless($property->verification_status === 'verified', 409, 'Property verification is required.');
        $data = $request->validate([
            'title' => 'required|string|max:160',
            'placement_type' => ['required',Rule::in(['feed_post','story','reel','video','group_post','pinned_post'])],
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:1|max:1000000|decimal:0,2',
            'currency' => ['required', Rule::in(['EGP','SAR'])],
            'duration_hours' => 'nullable|integer|min:1|max:720',
            'delivery_days' => 'required|integer|min:1|max:90',
        ]);
        $allowed = match ($property->property_type) {
            'group' => ['group_post','pinned_post'],
            'professional_profile' => ['feed_post','story','reel','video'],
            'creator_account' => ['feed_post','story','reel','video'],
            default => ['feed_post','story','reel','video'],
        };
        if (!in_array($data['placement_type'], $allowed, true)) {
            throw ValidationException::withMessages(['placement_type'=>'Unsupported placement for this property type.']);
        }
        if (($data['currency'] === 'EGP' && $property->country_code !== 'EG') ||
            ($data['currency'] === 'SAR' && $property->country_code !== 'SA')) {
            throw ValidationException::withMessages(['currency'=>'Currency must match the publisher country.']);
        }
        $minor = (int) round(((float)$data['price']) * 100);
        $property->adPackages()->create([
            'title'=>$data['title'],
            'placement_type'=>$data['placement_type'],
            'description'=>$data['description'] ?? null,
            'price_minor'=>$minor,
            'currency'=>$data['currency'],
            'duration_hours'=>$data['duration_hours'] ?? null,
            'delivery_days'=>$data['delivery_days'],
        ]);
        return redirect()->route('ads.publisher.index')->with('notice','Placement package submitted. It is not available to advertisers until reviewed.');
    }

    public function storeMetrics(Request $request, PublisherProperty $property)
    {
        $property = $this->property($request,$property);
        $data = $request->validate([
            'observed_on' => 'required|date|before_or_equal:today|after:2018-01-01',
            'content_category'=>'nullable|string|max:120',
            'follower_count'=>'nullable|integer|min:0|max:2000000000',
            'views'=>'nullable|integer|min:0|max:2000000000',
            'reach'=>'nullable|integer|min:0|max:2000000000',
            'reactions'=>'nullable|integer|min:0|max:2000000000',
            'comments'=>'nullable|integer|min:0|max:2000000000',
            'shares'=>'nullable|integer|min:0|max:2000000000',
            'evidence_url'=>'nullable|url|max:2048',
        ]);
        $counts = ['follower_count','views','reach','reactions','comments','shares'];
        if (!collect($counts)->contains(fn($key)=>isset($data[$key]))) {
            throw ValidationException::withMessages(['views'=>'Enter at least one measurement.']);
        }
        if (!empty($data['evidence_url'])) {
            $url = $data['evidence_url'];
            $host = strtolower(parse_url($url,PHP_URL_HOST) ?? '');
            $allowed = $property->platform === 'facebook'
                ? ['facebook.com','www.facebook.com','m.facebook.com','fb.com','www.fb.com']
                : ['instagram.com','www.instagram.com'];
            if (strtolower(parse_url($url,PHP_URL_SCHEME) ?? '') !== 'https' || !in_array($host,$allowed,true)) {
                throw ValidationException::withMessages(['evidence_url'=>'Use a public HTTPS URL on the same social platform.']);
            }
        }
        $property->metricSnapshots()->create($data + ['source_type'=>'self_reported']);
        return redirect()->route('ads.publisher.index')->with('notice','Metrics saved as self-reported, not verified.');
    }

    public function reviewPackage(Request $request, PublisherAdPackage $package)
    {
        abort_unless(config('vibsies_ads.enabled'),404);
        abort_unless($request->user()->is_admin,403);
        $data = $request->validate(['decision'=>['required',Rule::in(['approved','rejected'])],'note'=>'nullable|string|max:2000']);
        abort_unless($package->property->verification_status === 'verified',409,'Publisher is not verified.');
        $package->forceFill([
            'moderation_status'=>$data['decision'],
            'is_active'=>$data['decision']==='approved',
        ])->save();
        return back()->with('notice','Package moderation saved.');
    }

    public function reviewMetrics(Request $request, PublisherMetricSnapshot $snapshot)
    {
        abort_unless(config('vibsies_ads.enabled'),404);
        abort_unless($request->user()->is_admin,403);
        $decision = $request->validate(['decision'=>['required',Rule::in(['verified','rejected'])]]);
        abort_unless($snapshot->property->verification_status === 'verified',409,'Publisher is not verified.');
        abort_if(empty($snapshot->evidence_url) && $decision['decision']==='verified',409,'Metrics require corroborating evidence to verify.');
        $snapshot->forceFill(['verification_status'=>$decision['decision']])->save();
        return back()->with('notice','Metric review saved.');
    }
}
