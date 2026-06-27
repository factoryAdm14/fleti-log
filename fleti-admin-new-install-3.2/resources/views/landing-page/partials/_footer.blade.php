@php
    $footerLogo = getSession('footer_logo') ?: getSession('header_logo');
    $email = getSession('business_contact_email');
    $contactNumber = getSession('business_contact_phone');
    $businessAddress = getSession('business_address');
    $businessName = getSession('business_name');
    $footerContent = landingPageConfig(key: 'footer_contents', settingsType: FOOTER)?->value ?? null;
    $links = \Modules\BusinessManagement\Entities\SocialLink::where(['is_active' => 1])->orderBy('name', 'asc')->get();
    $driverAppVersionControlForAndroid = businessConfig(key: DRIVER_APP_VERSION_CONTROL_FOR_ANDROID, settingsType: APP_VERSION)?->value ?? null;
    $driverAppVersionControlForIos = businessConfig(key: DRIVER_APP_VERSION_CONTROL_FOR_IOS, settingsType: APP_VERSION)?->value ?? null;
    $customerAppVersionControlForAndroid = businessConfig(key: CUSTOMER_APP_VERSION_CONTROL_FOR_ANDROID, settingsType: APP_VERSION)?->value ?? null;
    $customerAppVersionControlForIos = businessConfig(key: CUSTOMER_APP_VERSION_CONTROL_FOR_IOS, settingsType: APP_VERSION)?->value ?? null;

    $socialIcons = [
        'facebook' => 'bi-facebook',
        'instagram' => 'bi-instagram',
        'twitter' => 'bi-twitter-x',
        'linkedin' => 'bi-linkedin',
    ];
@endphp

@if(landingPageConfig(key: 'is_newsletter_enabled', settingsType: NEWSLETTER)?->value == 1)
    @php($newsLetter = landingPageConfig(key: INTRO_CONTENTS, settingsType: NEWSLETTER)?->value ?? null)
    <section class="fleti-newsletter">
        <div class="container">
            <div class="fleti-newsletter__card">
                <div class="row align-items-center g-4">
                    <div class="col-lg-7">
                        <h4>{!! $newsLetter && $newsLetter['title'] ? change_text_color_or_bg($newsLetter['title']) : translate('GET ALL UPDATES & EXCITING NEWS') !!}</h4>
                        <p>{!! $newsLetter && $newsLetter['subtitle'] ? change_text_color_or_bg($newsLetter['subtitle']) : translate('Subscribe to out newsletters to receive all the latest activity we provide for you') !!}</p>
                    </div>
                    <div class="col-lg-5">
                        <form action="{{ route('newsletter-subscription.store') }}" method="POST" class="fleti-newsletter__form">
                            @csrf
                            <input type="email" name="email" placeholder="{{ translate('Type email...') }}" autocomplete="off" required>
                            <button type="submit">{{ translate('Subscribe ') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif

<footer class="fleti-footer">
    <div class="container">
        <div class="fleti-footer__grid">
            <div class="fleti-footer__brand">
                <a href="{{ route('index') }}">
                    <img src="{{ $footerLogo ? dynamicStorage(path: 'storage/app/public/business/'.$footerLogo) : dynamicAsset(path: 'public/landing-page/assets/img/logo.png') }}"
                         alt="{{ $businessName ?? 'Fleti' }}">
                </a>
                <p>{!! $footerContent && $footerContent['title'] ? change_text_color_or_bg($footerContent['title']) : translate('Connect with our social media and other sites to keep up to date') !!}</p>
                @if($links->isNotEmpty())
                    <ul class="fleti-footer__social">
                        @foreach($links as $link)
                            @if(isset($socialIcons[$link->name]))
                                <li>
                                    <a href="{{ $link->link }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $link->name }}">
                                        <i class="bi {{ $socialIcons[$link->name] }}"></i>
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @endif
            </div>

            <div>
                <div class="fleti-footer__title">Navegação</div>
                <ul class="fleti-footer__links">
                    <li><a href="{{ route('index') }}">{{ translate('Home') }}</a></li>
                    <li><a href="{{ route('about-us') }}">{{ translate('About Us') }}</a></li>
                    <li><a href="{{ route('contact-us') }}">{{ translate('Contact Us') }}</a></li>
                    <li><a href="{{ route('privacy') }}">{{ translate('Privacy Policy') }}</a></li>
                    <li><a href="{{ route('terms') }}">{{ translate('Terms & Condition') }}</a></li>
                    <li><a href="{{ route('legal') }}">{{ translate('legal') }}</a></li>
                    <li><a href="{{ route('refund-policy') }}">{{ translate('refund_policy') }}</a></li>
                </ul>
            </div>

            <div>
                <div class="fleti-footer__title">{{ translate('Contact Us') }}</div>
                <div class="fleti-footer__contact-item">
                    <i class="bi bi-envelope"></i>
                    <a href="mailto:{{ $email ?? 'contato@fleti.com.br' }}">{{ $email ?? 'contato@fleti.com.br' }}</a>
                </div>
                @if($contactNumber)
                    <div class="fleti-footer__contact-item">
                        <i class="bi bi-telephone"></i>
                        <a href="tel:{{ $contactNumber }}">{{ $contactNumber }}</a>
                    </div>
                @endif
                @if($businessAddress)
                    <div class="fleti-footer__contact-item">
                        <i class="bi bi-geo-alt"></i>
                        <span>{{ $businessAddress }}</span>
                    </div>
                @endif

                @if($customerAppVersionControlForAndroid || $customerAppVersionControlForIos || $driverAppVersionControlForAndroid || $driverAppVersionControlForIos)
                    <div class="fleti-footer__title mt-4">{{ translate('Download Our App') }}</div>
                    <div class="fleti-footer__apps">
                        @if($customerAppVersionControlForAndroid)
                            <a href="{{ $customerAppVersionControlForAndroid['app_url'] }}" target="_blank" rel="noopener noreferrer">
                                <img src="{{ dynamicAsset(path: 'public/landing-page/assets/img/play-store.png') }}" alt="Google Play">
                            </a>
                        @endif
                        @if($customerAppVersionControlForIos)
                            <a href="{{ $customerAppVersionControlForIos['app_url'] }}" target="_blank" rel="noopener noreferrer">
                                <img src="{{ dynamicAsset(path: 'public/landing-page/assets/img/app-store.png') }}" alt="App Store">
                            </a>
                        @endif
                        @if($driverAppVersionControlForAndroid)
                            <a href="{{ $driverAppVersionControlForAndroid['app_url'] }}" target="_blank" rel="noopener noreferrer">
                                <img src="{{ dynamicAsset(path: 'public/landing-page/assets/img/play-store.png') }}" alt="{{ translate('Driver App') }}">
                            </a>
                        @endif
                        @if($driverAppVersionControlForIos)
                            <a href="{{ $driverAppVersionControlForIos['app_url'] }}" target="_blank" rel="noopener noreferrer">
                                <img src="{{ dynamicAsset(path: 'public/landing-page/assets/img/app-store.png') }}" alt="{{ translate('Driver App') }}">
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="fleti-footer__bottom">
            <span>{{ getSession('copyright_text') ?? '© ' . date('Y') . ' Fleti Log Ltda. — CNPJ: 50.228.256/0001-29. ' . translate('All Rights Reserved') }}</span>
            <span>{{ $businessName ?? 'Fleti Log' }}</span>
        </div>
    </div>
</footer>
