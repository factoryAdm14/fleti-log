<?php
/**
 * Bootstrap demo master data + test accounts on production.
 * Run from Laravel root: php scripts/seed_demo_data.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\FareManagement\Entities\ParcelFare;
use Modules\FareManagement\Entities\ParcelFareWeight;
use Modules\FareManagement\Entities\TripFare;
use Modules\FareManagement\Entities\ZoneWiseDefaultTripFare;
use Modules\ParcelManagement\Entities\ParcelCategory;
use Modules\ParcelManagement\Entities\ParcelWeight;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserLastLocation;
use Modules\UserManagement\Service\Interfaces\CustomerServiceInterface;
use Modules\UserManagement\Service\Interfaces\DriverServiceInterface;
use Modules\VehicleManagement\Entities\Vehicle;
use Modules\VehicleManagement\Entities\VehicleBrand;
use Modules\VehicleManagement\Entities\VehicleCategory;
use Modules\VehicleManagement\Entities\VehicleModel;
use Modules\ZoneManagement\Entities\Zone;
use MatanYadaev\EloquentSpatial\Objects\Point;

const DEMO_CUSTOMER_PHONE = '+5544999000001';
const DEMO_DRIVER_PHONE = '+5544999000002';
const DEMO_PASSWORD = 'Test1234!';

$result = ['ok' => true, 'steps' => [], 'accounts' => []];

function step(array &$result, string $name, callable $fn): void
{
    try {
        $value = $fn();
        $result['steps'][] = ['step' => $name, 'status' => 'ok', 'detail' => $value];
    } catch (Throwable $e) {
        $result['ok'] = false;
        $result['steps'][] = ['step' => $name, 'status' => 'error', 'detail' => $e->getMessage()];
        throw $e;
    }
}

try {
    DB::beginTransaction();

    step($result, 'zone', function () {
        $zone = Zone::where('is_active', 1)->first();
        if (!$zone) {
            throw new RuntimeException('No active zone found');
        }
        return $zone->id;
    });
    $zoneId = $result['steps'][count($result['steps']) - 1]['detail'];

    step($result, 'vehicle_category', function () {
        $cat = VehicleCategory::firstOrCreate(
            ['name' => 'Economico Demo'],
            ['description' => 'Categoria demo para testes', 'type' => 'car', 'is_active' => 1, 'image' => 'demo-category.png']
        );
        if (!$cat->is_active) {
            $cat->update(['is_active' => 1]);
        }
        return $cat->id;
    });
    $vehicleCategoryId = $result['steps'][count($result['steps']) - 1]['detail'];

    step($result, 'vehicle_brand_model', function () {
        $brand = VehicleBrand::firstOrCreate(
            ['name' => 'Fiat Demo'],
            ['description' => 'Demo', 'is_active' => 1, 'image' => 'demo-brand.png']
        );
        $model = VehicleModel::firstOrCreate(
            ['name' => 'Uno Demo', 'brand_id' => $brand->id],
            [
                'seat_capacity' => 4,
                'maximum_weight' => 300,
                'hatch_bag_capacity' => 2,
                'engine' => '1.0',
                'description' => 'Demo',
                'is_active' => 1,
                'image' => 'demo-model.png',
            ]
        );
        return ['brand_id' => $brand->id, 'model_id' => $model->id];
    });
    $brandModel = $result['steps'][count($result['steps']) - 1]['detail'];

    step($result, 'trip_fare', function () use ($zoneId, $vehicleCategoryId) {
        $default = ZoneWiseDefaultTripFare::firstOrCreate(
            ['zone_id' => $zoneId],
            [
                'base_fare' => 8.0,
                'base_fare_per_km' => 2.5,
                'waiting_fee_per_min' => 0.5,
                'cancellation_fee_percent' => 10,
                'min_cancellation_fee' => 5,
                'idle_fee_per_min' => 0.3,
                'trip_delay_fee_per_min' => 0.3,
                'penalty_fee_for_cancel' => 0,
                'fee_add_to_next' => 0,
                'category_wise_different_fare' => 0,
            ]
        );
        $fare = TripFare::firstOrCreate(
            ['zone_id' => $zoneId, 'vehicle_category_id' => $vehicleCategoryId],
            [
                'zone_wise_default_trip_fare_id' => $default->id,
                'base_fare' => 8.0,
                'base_fare_per_km' => 2.5,
                'waiting_fee_per_min' => 0.5,
                'cancellation_fee_percent' => 10,
                'min_cancellation_fee' => 5,
                'idle_fee_per_min' => 0.3,
                'trip_delay_fee_per_min' => 0.3,
                'penalty_fee_for_cancel' => 0,
                'fee_add_to_next' => 0,
            ]
        );
        return $fare->id;
    });

    step($result, 'parcel_master', function () use ($zoneId) {
        $weight = ParcelWeight::firstOrCreate(
            ['min_weight' => 0, 'max_weight' => 10],
            ['is_active' => 1]
        );
        $category = ParcelCategory::firstOrCreate(
            ['name' => 'Pacote Demo'],
            ['description' => 'Entrega demo', 'is_active' => 1, 'image' => 'demo-parcel.png']
        );
        $parcelFare = ParcelFare::firstOrCreate(
            ['zone_id' => $zoneId],
            [
                'base_fare' => 10.0,
                'return_fee' => 5.0,
                'cancellation_fee' => 3.0,
                'base_fare_per_km' => 0,
                'cancellation_fee_percent' => 0,
                'min_cancellation_fee' => 0,
            ]
        );
        ParcelFareWeight::firstOrCreate(
            [
                'parcel_fare_id' => $parcelFare->id,
                'parcel_weight_id' => $weight->id,
                'parcel_category_id' => $category->id,
                'zone_id' => $zoneId,
            ],
            [
                'base_fare' => 12.0,
                'return_fee' => 5.0,
                'cancellation_fee' => 3.0,
                'fare_per_km' => 2.0,
            ]
        );
        return [
            'parcel_weight_id' => $weight->id,
            'parcel_category_id' => $category->id,
        ];
    });
    $parcelMaster = $result['steps'][count($result['steps']) - 1]['detail'];

    /** @var CustomerServiceInterface $customerService */
    $customerService = app(CustomerServiceInterface::class);
    /** @var DriverServiceInterface $driverService */
    $driverService = app(DriverServiceInterface::class);

    step($result, 'customer_account', function () use ($customerService) {
        $existing = User::where('phone', DEMO_CUSTOMER_PHONE)->where('user_type', CUSTOMER)->first();
        if ($existing) {
            $existing->update([
                'password' => bcrypt(DEMO_PASSWORD),
                'is_active' => 1,
            ]);
            return $existing->id;
        }
        $customer = $customerService->create([
            'first_name' => 'Cliente',
            'last_name' => 'Demo',
            'phone' => DEMO_CUSTOMER_PHONE,
            'email' => 'cliente.demo@fleti.com.br',
            'password' => DEMO_PASSWORD,
        ]);
        return $customer->id;
    });
    $customerId = $result['steps'][count($result['steps']) - 1]['detail'];

    step($result, 'driver_account', function () use ($driverService) {
        $existing = User::where('phone', DEMO_DRIVER_PHONE)->where('user_type', DRIVER)->first();
        if ($existing) {
            $existing->update([
                'password' => bcrypt(DEMO_PASSWORD),
                'is_active' => 1,
                'gender' => 'male',
            ]);
            $existing->driverDetails?->update([
                'service' => ['ride_request', 'parcel'],
                'is_online' => 1,
                'availability_status' => 'available',
            ]);
            return $existing->id;
        }
        $driver = $driverService->create([
            'first_name' => 'Motorista',
            'last_name' => 'Demo',
            'phone' => DEMO_DRIVER_PHONE,
            'email' => 'motorista.demo@fleti.com.br',
            'password' => DEMO_PASSWORD,
            'gender' => 'male',
            'service' => json_encode(['ride_request', 'parcel']),
        ]);
        $driver->driverDetails?->update([
            'is_online' => 1,
            'availability_status' => 'available',
        ]);
        return $driver->id;
    });
    $driverId = $result['steps'][count($result['steps']) - 1]['detail'];

    step($result, 'driver_vehicle', function () use ($driverId, $brandModel, $vehicleCategoryId) {
        $vehicle = Vehicle::firstOrCreate(
            ['driver_id' => $driverId],
            [
                'ref_id' => 'DEMO-' . Str::upper(Str::random(6)),
                'brand_id' => $brandModel['brand_id'],
                'model_id' => $brandModel['model_id'],
                'category_id' => $vehicleCategoryId,
                'licence_plate_number' => 'ABC1D23',
                'licence_expire_date' => now()->addYear()->toDateString(),
                'fuel_type' => 'petrol',
                'ownership' => 'driver',
                'documents' => [],
                'is_active' => 1,
                'vehicle_request_status' => APPROVED,
            ]
        );
        $vehicle->update([
            'vehicle_request_status' => APPROVED,
            'is_active' => 1,
            'category_id' => $vehicleCategoryId,
        ]);
        return $vehicle->id;
    });

    step($result, 'driver_location', function () use ($driverId, $zoneId) {
        UserLastLocation::updateOrCreate(
            ['user_id' => $driverId],
            [
                'type' => DRIVER,
                'latitude' => -24.0460,
                'longitude' => -52.3780,
                'zone_id' => $zoneId,
            ]
        );
        return 'Campo Mourão';
    });

    DB::commit();

    $result['accounts'] = [
        'customer' => [
            'phone' => DEMO_CUSTOMER_PHONE,
            'email' => 'cliente.demo@fleti.com.br',
            'password' => DEMO_PASSWORD,
            'web' => 'https://fleti.com.br/client/',
            'id' => $customerId,
        ],
        'driver' => [
            'phone' => DEMO_DRIVER_PHONE,
            'email' => 'motorista.demo@fleti.com.br',
            'password' => DEMO_PASSWORD,
            'web' => 'https://fleti.com.br/driver/',
            'id' => $driverId,
        ],
        'zone_id' => $zoneId,
        'vehicle_category_id' => $vehicleCategoryId,
        'parcel_category_id' => $parcelMaster['parcel_category_id'],
        'pickup' => ['lat' => -24.0460, 'lng' => -52.3780, 'address' => 'Centro, Campo Mourão - PR'],
        'destination' => ['lat' => -24.0400, 'lng' => -52.3650, 'address' => 'Jardim Lar Paraná, Campo Mourão - PR'],
    ];
} catch (Throwable $e) {
    DB::rollBack();
    $result['ok'] = false;
    $result['error'] = $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit($result['ok'] ? 0 : 1);
