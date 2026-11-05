@extends('layouts.public')
@section('title', $property->display_name.' | Vibsies Publisher Reputation')
@section('seo_description', app()->getLocale()==='ar' ? 'ملخص تقييمات معلنين معتمدة لحملات مكتملة، مع عدد المعلنين المستقلين.': 'Approved advertiser feedback from completed placements, with transparent independent advertiser counts.')
@section('content')
<h1>{{ $property->display_name }}</h1>
<p>{{ app()->getLocale()==='ar' ? 'ملخص سمعة الناشر' : 'Publisher reputation summary' }}</p>
<p><strong>{{ number_format($summary['average_overall'],1) }} / 5</strong></p>
<p>{{ app()->getLocale()==='ar' ? 'تقييمات حملات معتمدة' : 'Approved completed campaign reviews' }}: {{ $summary['review_count'] }}</p>
<p>{{ app()->getLocale()==='ar' ? 'المعلنون المستقلون' : 'Independent advertisers' }}: {{ $summary['independent_advertisers'] }}</p>
<p>{{ app()->getLocale()==='ar' ? 'خلال آخر 12 شهرًا. لا يمثل هذا تقييمًا مضمونًا للوصول أو المبيعات.' : 'Last 12 months. These ratings do not guarantee reach, sales or campaign performance.' }}</p>
<p><a href="{{ route('seo.publishers') }}">{{ app()->getLocale()==='ar' ? 'دليل الإعلان عبر الناشرين' : 'Publisher advertising guide' }}</a></p>
@endsection
