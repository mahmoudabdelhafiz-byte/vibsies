@extends('layouts.public')
@section('title',app()->getLocale()==='ar'?'الإعلان عبر الناشرين وصناع المحتوى | فيبسيز':'Advertise with Social Publishers & Creators | Vibsies')
@section('seo_description',app()->getLocale()==='ar'?'تعرّف على الإعلان عبر صفحات فيسبوك والمجموعات وصناع محتوى إنستغرام مع مطابقة الجمهور والأدلة والمراجعة اليدوية في فيبسيز.':'Explore relevant Facebook pages, groups and Instagram creators with transparent audience evidence and manually reviewed advertising placements on Vibsies.')
@section('content')
@if(app()->getLocale()==='ar')
<h1>الإعلان عبر الناشرين وصناع المحتوى</h1>
<p>يساعد فيبسيز المعلنين على اكتشاف صفحات فيسبوك والمجموعات وصناع محتوى إنستغرام بحسب فئة الجمهور والمدينة ونوع الإعلان والميزانية.</p>
<h2>كيف تختار الناشر المناسب؟</h2>
<p>ابدأ بملاءمة الجمهور والموقع ونوع المحتوى. تحقق من ملكية الحساب والأدلة المتاحة ولا تعتمد على عدد المتابعين وحده. الأرقام التي يقدمها الناشر دون مراجعة تبقى غير موثقة.</p>
<h2>الشفافية في التنفيذ والتقييم</h2>
<p>تتضمن العملية عروضًا مبدئية، ودليل نشر، ومراجعة المعلن. لا تعرض تقييمات عامة إلا بعد توفر عدد كافٍ من تجارب المعلنين الموثوقة.</p>
<p><strong>الحالة الحالية:</strong> ميزة الإعلانات قيد الاختبار؛ تحصيل المدفوعات وصرف المستحقات غير مفعّل.</p>
@else
<h1>Advertise with social publishers and creators</h1>
<p>Vibsies helps advertisers discover Facebook pages, communities and Instagram creators by audience category, city, placement format and budget.</p>
<h2>How to choose the right publisher</h2>
<p>Start with audience relevance, location and content format. Check account ownership and available evidence rather than relying on follower totals alone. Self-reported reach is not verified reach.</p>
<h2>Transparent delivery and feedback</h2>
<p>The planned process includes preliminary offers, public publication evidence and advertiser review. Public ratings are withheld until enough authentic, moderated campaign feedback exists.</p>
<p><strong>Current status:</strong> Advertising is being tested. Payment collection and publisher payouts are disabled.</p>
@endif
<p><a href="{{ route('home') }}">{{ __('Find creators') }}</a></p>
@endsection
