@php
    $pageKey = $pageKey ?? 'about_us';
    $pageTitle = $pageTitle ?? translate('About Us');
    $pageValue = $data?->value ?? null;
@endphp

@extends('landing-page.layouts.master')
@section('title', $pageTitle . ' | Fleti Log')

@push('seo')
    <meta name="description" content="{{ fletiBusinessPageShortDescription($pageValue, $pageKey) }}"/>
@endpush

@section('content')
    <div class="container pt-3">
        <section class="page-header bg__img"
                 data-img="{{ $pageValue['image'] ?? null ? dynamicStorage(path: 'storage/app/public/business/pages/'.$pageValue['image']) : dynamicAsset(path: 'public/landing-page/assets/img/page-header.png') }}"
                 style="background-image: url({{ ($pageValue['image'] ?? null) ? dynamicStorage(path: 'storage/app/public/business/pages/'.$pageValue['image']) : dynamicAsset(path: 'public/landing-page/assets/img/page-header.png') }});">
            <h1 class="title">{{ $pageTitle }}</h1>
            <p class="mt-2">{{ fletiBusinessPageShortDescription($pageValue, $pageKey) }}</p>
        </section>
    </div>

    <section class="terms-section py-5">
        <div class="container">
            <div class="fleti-legal-page-card">
                {!! fletiBusinessPageHtml($pageValue, $pageKey) !!}
            </div>
        </div>
    </section>
@endsection
