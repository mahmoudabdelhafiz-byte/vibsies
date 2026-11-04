<?php

namespace App\Http\Controllers;

use App\Models\CreatorProfile;
use Illuminate\Routing\Controller;

class PublicSeoController extends Controller
{
    public function sitemap()
    {
        $urls=[route('home')];
        CreatorProfile::query()->where('status','approved')
            ->whereHas('packages',fn($q)=>$q->where('is_active',true)->where('moderation_status','approved'))
            ->orderBy('id')->chunk(250,function($creators)use(&$urls){
                foreach($creators as $creator) $urls[]=route('creators.show',$creator->slug);
            });
        $escape=fn($value)=>htmlspecialchars($value,ENT_XML1|ENT_QUOTES,'UTF-8');
        $xml='<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml.='<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach($urls as $url) $xml.='<url><loc>'.$escape($url).'</loc></url>';
        $xml.='</urlset>';
        return response($xml,200,['Content-Type'=>'application/xml; charset=UTF-8','X-Content-Type-Options'=>'nosniff']);
    }

    public function robots()
    {
        $body="User-agent: *\n";
        $body.="Disallow: /admin\nDisallow: /ads/\nDisallow: /dashboard\nDisallow: /login\nDisallow: /register\nDisallow: /r/\n";
        $body.="Sitemap: ".route('seo.sitemap')."\n";
        return response($body,200,['Content-Type'=>'text/plain; charset=UTF-8']);
    }
}
