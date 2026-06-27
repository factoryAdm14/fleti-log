#!/usr/bin/env php
<?php
/**
 * Configure Google Maps API keys in business_settings (admin → Third Party → Google Map API).
 *
 * Usage (from repo root):
 *   php scripts/configure_google_maps.php
 *   php scripts/configure_google_maps.php --client-key=AIza... --server-key=AIza...
 */

$adminRoot = dirname(__DIR__);
$localAdmin = $adminRoot . '/fleti-admin-new-install-3.2';
if (is_dir($localAdmin . '/vendor')) {
    $adminRoot = $localAdmin;
} elseif (!is_dir($adminRoot . '/vendor')) {
    fwrite(STDERR, "Laravel root not found: {$adminRoot}\n");
    exit(1);
}

chdir($adminRoot);
require $adminRoot . '/vendor/autoload.php';
$app = require $adminRoot . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Modules\BusinessManagement\Service\Interfaces\BusinessSettingServiceInterface;

$options = getopt('', ['client-key:', 'server-key:']);
$defaultKey = 'AIzaSyDJndkYZUvH2_uLZyuk9Z8IrtUq5FvHe7Y';
$clientKey = $options['client-key'] ?? getenv('GOOGLE_MAP_CLIENT_KEY') ?: $defaultKey;
$serverKey = $options['server-key'] ?? getenv('GOOGLE_MAP_SERVER_KEY') ?: $defaultKey;

/** @var BusinessSettingServiceInterface $service */
$service = app(BusinessSettingServiceInterface::class);
$service->storeGoogleMapApi([
    'map_api_key' => $clientKey,
    'map_api_key_server' => $serverKey,
]);

Illuminate\Support\Facades\Artisan::call('config:cache');
Illuminate\Support\Facades\Artisan::call('cache:clear');

echo json_encode([
    'ok' => true,
    'map_api_key_set' => !empty($clientKey),
    'map_api_key_server_set' => !empty($serverKey),
    'client_key_preview' => substr($clientKey, 0, 8) . '...',
], JSON_PRETTY_PRINT) . PHP_EOL;
