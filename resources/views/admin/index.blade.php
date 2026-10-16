<h1>Vibsies administration</h1><p>Decisions are recorded in an audit trail. No financial or payment actions are available.</p>
<h2>Creators pending review</h2>@foreach($creators as $creator)<article><b>{{ $creator->display_name }}</b> — {{ $creator->category }}<form method="post" action="{{ route('admin.creators.decide',$creator) }}">@csrf<input name="reason" placeholder="Reason"><button name="decision" value="approved">Approve</button><button name="decision" value="rejected">Reject</button></form></article>@endforeach
<h2>Packages awaiting approval</h2>@foreach($packages as $package)<article><b>{{ $package->title }}</b> — {{ $package->price_minor }} minor units<form method="post" action="{{ route('admin.packages.decide',$package) }}">@csrf<input name="reason" placeholder="Reason"><button name="decision" value="approved">Approve</button><button name="decision" value="rejected">Reject</button></form></article>@endforeach
<h2>Open disputes</h2>@foreach($disputes as $dispute)<article><p>{{ $dispute->reason }}</p><form method="post" action="{{ route('admin.disputes.decide',$dispute) }}">@csrf<textarea name="reason" minlength="10" required placeholder="Document review and outcome"></textarea><button name="decision" value="resolved">Resolve</button><button name="decision" value="escalated">Escalate</button></form></article>@endforeach
<h2>Pending verification requests</h2>
@foreach($verifications as $verification)
<article>
 <strong>{{ $verification->creator->display_name }}</strong> — {{ $verification->type }}
 <p>Reference hash: {{ $verification->reference_hash }}</p>
 <form method="post" action="{{ route('admin.verifications.review',$verification) }}">
  @csrf
  <label>Decision rationale<textarea name="reason" minlength="5" maxlength="1000" required></textarea></label>
  <label>Expiry, where applicable<input type="date" name="expires_at"></label>
  <button type="submit" name="decision" value="verified">Verify</button>
  <button type="submit" name="decision" value="rejected">Reject</button>
 </form>
</article>
@endforeach