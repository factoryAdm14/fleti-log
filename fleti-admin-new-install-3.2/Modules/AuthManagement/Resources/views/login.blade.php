<!DOCTYPE html>
<html lang="{{ defaultLang() }}" dir="{{ session()->get('direction') ?? 'ltr' }}">

<head>
    @php($logo = getSession('header_logo'))
    @php($favicon = getSession('favicon'))
  <!-- Page Title -->
    <title>{{ translate('admin_login') }}</title>
    <!-- Meta Data -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="description" content=""/>
    <meta name="keywords" content=""/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ $favicon ? dynamicStorage('storage/app/public/business/' . $favicon) : '' }}"/>

    <link href="{{ dynamicAsset('public/assets/admin-module/css/fonts/google.css') }}" rel="stylesheet">

    <link rel="stylesheet" href="{{ dynamicAsset('public/assets/admin-module/css/bootstrap.min.css') }}"/>
    <link rel="stylesheet" href="{{ dynamicAsset('public/assets/admin-module/css/bootstrap-icons.min.css') }}"/>
    <link rel="stylesheet" href="{{ dynamicAsset('public/assets/admin-module/css/toastr.css') }}"/>
    <link rel="stylesheet" href="{{ dynamicAsset('public/assets/admin-module/css/style.css') }}"/>
    <link rel="stylesheet" href="{{ dynamicAsset('public/assets/admin-module/css/custom.css') }}"/>
    <link rel="stylesheet" href="{{ dynamicAsset('public/assets/admin-module/css/fleti-login-modern.css') }}"/>
</head>

<body class="fleti-login-page">
<div class="fleti-login-shell">
    <div class="fleti-login-card">
        <div class="fleti-login-brand">
            <img src="{{ onErrorImage(
                $logo,
                dynamicStorage('storage/app/public/business') . '/' . $logo,
                dynamicAsset('public/assets/admin-module/img/logo.png'),
                'business/',
            ) }}" alt="{{ businessConfig('business_name')?->value ?? 'Fleti' }}">
            <h1>{{ businessConfig('business_name')?->value ?? 'Fleti' }}</h1>
            <p>{{ translate('sign_in_to_stay_connected') }}</p>
            <span class="fleti-login-version">
                {{ translate('Software_Version') }} {{ config('app.software_version') }}
            </span>
        </div>

        <form action="{{ route('admin.auth.login') }}" enctype="multipart/form-data" method="POST" id="login-form">
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label">{{ translate('email') }}</label>
                <input type="email" name="email" class="form-control"
                       placeholder="{{ translate('email') }}" required
                       id="email"
                       value="{{ request()->cookie('remember_email') }}">
            </div>

            <div class="mb-3 input-group_tooltip">
                <label for="password" class="form-label">{{ translate('password') }}</label>
                <input type="password" name="password" id="password" class="form-control"
                       placeholder="{{ translate('ex') }}: ********"
                       autocomplete="current-password" required>
                <i id="password-eye" class="bi bi-eye-slash-fill tooltip-icon"></i>
            </div>

            <div class="fleti-login-remember mb-4">
                <input type="checkbox" name="remember" id="remember" {{ request()->cookie('remember_checked') ? 'checked' : '' }}>
                <label for="remember" class="mb-0">{{ translate('remember_me') }}</label>
            </div>

            @php($recaptcha = businessConfig('recaptcha')?->value)
            <div class="fleti-login-captcha mb-4">
                @if(isset($recaptcha) && $recaptcha['status'] == 1)
                    <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                    <input type="hidden" name="set_default_captcha" id="set_default_captcha_value" value="0">

                    <div class="row d-none" id="reload-captcha">
                        <div class="col-6 pe-2">
                            <input type="text" class="form-control"
                                   name="default_captcha_value" value=""
                                   placeholder="{{ translate('Enter captcha') }}" autocomplete="off">
                        </div>
                        <div class="col-6">
                            <a class="refresh-recaptcha d-block">
                                <img src="{{ URL('/admin/auth/code/captcha/1') }}"
                                     class="w-100 rounded border"
                                     id="default_recaptcha_id" alt="{{ translate('recaptcha') }}">
                            </a>
                        </div>
                    </div>
                @else
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="text" class="form-control"
                                   name="default_captcha_value" value=""
                                   placeholder="{{ translate('Enter captcha') }}" autocomplete="off">
                        </div>
                        <div class="col-6">
                            <a class="refresh-recaptcha d-block">
                                <img src="{{ URL('/admin/auth/code/captcha/1') }}"
                                     class="w-100 rounded border"
                                     id="default_recaptcha_id" alt="{{ translate('recaptcha') }}">
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            <button class="btn btn-primary w-100" id="signInBtn" type="submit">
                {{ translate('sign_in') }}
            </button>
        </form>

        @if (config('app.app_mode', env('APP_MODE')) == 'demo')
            <div class="fleti-login-demo">
                <div>
                    <div>{{ translate('email') }}: admin@admin.com</div>
                    <div>{{ translate('password') }}: 12345678</div>
                </div>
                <button type="button" class="btn btn-primary btn-sm" onclick="copyCredentials()">
                    <i class="bi bi-copy"></i>
                </button>
            </div>
        @endif
    </div>
</div>

<script src="{{ dynamicAsset('public/assets/admin-module/js/jquery-3.6.0.min.js') }}"></script>
<script src="{{ dynamicAsset('public/assets/admin-module/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ dynamicAsset('public/assets/admin-module/js/toastr.js') }}"></script>
<script src="{{ dynamicAsset('public/assets/admin-module/js/login.js') }}"></script>

{!! Toastr::message() !!}

@if (config('app.app_mode', env('APP_MODE')) == 'demo')
    <script>
        "use strict";

        function copyCredentials() {
            document.getElementById('email').value = 'admin@admin.com';
            document.getElementById('password').value = '12345678';
            toastr.success('Copied successfully!', 'Success!', {
                CloseButton: true,
                ProgressBar: true
            });
        }
    </script>
@endif

@if ($errors->any())
    <script>
        "use strict";
        @foreach ($errors->all() as $error)
        toastr.error('{{ $error }}', 'Error', {
            CloseButton: true,
            ProgressBar: true
        });
        @endforeach
    </script>
@endif

@if(isset($recaptcha) && $recaptcha['status'] == 1)
    <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptcha['site_key'] }}"></script>
    <script>
        $(document).ready(function () {
            $('#signInBtn').click(function (e) {
                if ($('#set_default_captcha_value').val() == 1) {
                    $('#login-form').submit();
                    return true;
                }

                e.preventDefault();

                if (typeof grecaptcha === 'undefined') {
                    toastr.error('Invalid recaptcha key provided. Please check the recaptcha configuration.');
                    $('#reload-captcha').removeClass('d-none');
                    $('#set_default_captcha_value').val('1');
                    return;
                }

                grecaptcha.ready(function () {
                    grecaptcha.execute('{{ $recaptcha['site_key'] }}', {action: 'submit'}).then(function () {
                        $('#login-form').submit();
                    });
                });

                window.onerror = function (message) {
                    var errorMessage = 'An unexpected error occurred. Please check the recaptcha configuration';
                    if (message.includes('Invalid site key')) {
                        errorMessage = 'Invalid site key provided. Please check the recaptcha configuration.';
                    } else if (message.includes('not loaded in api.js')) {
                        errorMessage = 'reCAPTCHA API could not be loaded. Please check the recaptcha API configuration.';
                    }

                    $('#reload-captcha').removeClass('d-none');
                    $('#set_default_captcha_value').val('1');
                    toastr.error(errorMessage);
                    return true;
                };
            });
        });
    </script>
@endif

<script>
    $('.refresh-recaptcha').on('click', function () {
        let url = "{{ route('admin.auth.default-captcha', ':tmp') }}";
        document.getElementById('default_recaptcha_id').src = url.replace(':tmp', Math.random());
    });
</script>

</body>
</html>
