@php
    use Modules\BlogManagement\Entities\BlogSetting;
    $logo = getSession('header_logo');
    $isBlogEnabled = BlogSetting::where(['key_name' => 'is_enabled', 'settings_type' => BLOG_PAGE])->first()?->value;
@endphp
<header class="fleti-header">
    <div class="container position-relative">
        <div class="fleti-header__inner">
            <a href="{{ route('index') }}" class="fleti-header__logo">
                <img src="{{ $logo ? dynamicStorage(path: 'storage/app/public/business/'.$logo) : dynamicAsset(path: 'public/landing-page/assets/img/logo.png') }}"
                     alt="{{ getSession('business_name') ?? 'Fleti' }}">
            </a>

            <button type="button" class="fleti-header__toggle" id="fletiNavToggle" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>

            <div class="fleti-header__menu" id="fletiNavMenu">
                <ul class="fleti-header__nav">
                    <li><a href="{{ route('index') }}" class="{{ Request::is('/') ? 'active' : '' }}">{{ translate('Home') }}</a></li>
                    <li><a href="{{ route('about-us') }}" class="{{ Request::is('about-us') ? 'active' : '' }}">{{ translate('About Us') }}</a></li>
                    @if($isBlogEnabled)
                        <li><a href="{{ route('blog.index') }}" class="{{ Request::is('blog*') ? 'active' : '' }}">{{ translate('Blog') }}</a></li>
                    @endif
                    <li><a href="{{ route('privacy') }}" class="{{ Request::is('privacy') ? 'active' : '' }}">{{ translate('Privacy Policy') }}</a></li>
                    <li><a href="{{ route('terms') }}" class="{{ Request::is('terms') ? 'active' : '' }}">{{ translate('Terms & Condition') }}</a></li>
                </ul>
                <a href="{{ route('contact-us') }}" class="fleti-btn fleti-btn--primary fleti-header__cta {{ Request::is('contact-us') ? 'active' : '' }}">
                    {{ translate('Contact Us') }}
                </a>
            </div>
        </div>
    </div>
</header>
