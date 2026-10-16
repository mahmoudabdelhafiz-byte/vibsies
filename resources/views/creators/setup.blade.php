<h1>Creator studio</h1>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach<form method="post" action="{{ route('creator.store') }}">@csrf<input name="display_name" placeholder="Display name" value="{{ old('display_name',$creator?->display_name) }}" required><input name="category" placeholder="Category" value="{{ old('category',$creator?->category) }}" required><textarea name="bio">{{ old('bio',$creator?->bio) }}</textarea><select name="country_code"><option value="EG">Egypt</option><option value="SA">Saudi Arabia</option></select><button>Save creator profile</button></form>@if($creator)<form method="post" action="{{ route('creator.package') }}">@csrf<input name="title" placeholder="Package title" required><textarea name="description"></textarea><input name="price" type="number" min="1" step="0.01" required><input name="turnaround_days" type="number" min="1" value="7" required><input name="revision_limit" type="number" min="0" value="1" required><button>Create package</button></form>@endif
@if($creator)
<h2>Request verification</h2>
<p>Submit a reference for manual review. This does not grant a verified badge automatically. Do not enter passwords, banking credentials, or full sensitive identity documents.</p>
<form method="post" action="{{ route('verification.submit') }}">
 @csrf
 <label>Type <select name="type"><option value="identity">Identity</option><option value="social">Social profile</option><option value="advertising_license">Advertising license</option></select></label>
 <label>Reference <input name="reference" required minlength="4" maxlength="200"></label>
 <button type="submit">Submit for review</button>
</form>
@endif