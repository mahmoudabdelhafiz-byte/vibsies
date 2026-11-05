<form method="post" action="{{ route('locale.switch') }}" class="locale-switch" aria-label="{{ app()->getLocale()==='ar' ? 'اللغة' : 'Language' }}">
 @csrf
 <button name="locale" value="en" type="submit" lang="en" @if(app()->getLocale()==='en') aria-current="true" @endif>English</button>
 <button name="locale" value="ar" type="submit" lang="ar" @if(app()->getLocale()==='ar') aria-current="true" @endif>العربية</button>
</form>
