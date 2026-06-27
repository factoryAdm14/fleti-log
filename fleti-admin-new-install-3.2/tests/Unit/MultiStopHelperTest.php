<?php

namespace Tests\Unit;

use InvalidArgumentException;
use Modules\TripManagement\Lib\MultiStopHelper;
use Tests\TestCase;

class MultiStopHelperTest extends TestCase
{
    public function test_parse_stops_accepts_array(): void
    {
        $stops = [
            ['type' => 'pickup', 'address' => 'A', 'latitude' => -23.5, 'longitude' => -46.6],
            ['type' => 'dropoff', 'address' => 'B', 'latitude' => -23.6, 'longitude' => -46.7],
        ];

        $this->assertSame($stops, MultiStopHelper::parseStops($stops));
    }

    public function test_parse_stops_decodes_json_string(): void
    {
        $json = json_encode([
            ['type' => 'pickup', 'address' => 'A', 'latitude' => 1, 'longitude' => 2],
            ['type' => 'dropoff', 'address' => 'B', 'latitude' => 3, 'longitude' => 4],
        ]);

        $this->assertCount(2, MultiStopHelper::parseStops($json));
    }

    public function test_parse_stops_rejects_invalid_payload(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MultiStopHelper::parseStops('not-json');
    }

    public function test_validate_stops_requires_pickup_and_dropoff(): void
    {
        $this->expectException(InvalidArgumentException::class);

        MultiStopHelper::validateStops([
            ['type' => 'pickup', 'address' => 'A', 'latitude' => 1, 'longitude' => 2],
            ['type' => 'pickup', 'address' => 'B', 'latitude' => 3, 'longitude' => 4],
        ]);
    }

    public function test_validate_stops_requires_minimum_two_stops(): void
    {
        $this->expectException(InvalidArgumentException::class);

        MultiStopHelper::validateStops([
            ['type' => 'pickup', 'address' => 'A', 'latitude' => 1, 'longitude' => 2],
        ]);
    }

    public function test_validate_stops_accepts_valid_route(): void
    {
        $this->expectNotToPerformAssertions();

        MultiStopHelper::validateStops([
            ['type' => 'pickup', 'address' => 'A', 'latitude' => -23.5, 'longitude' => -46.6, 'stop_order' => 2],
            ['type' => 'dropoff', 'address' => 'B', 'latitude' => -23.6, 'longitude' => -46.7, 'stop_order' => 1],
        ]);
    }

    public function test_optimize_stop_order_sorts_and_reindexes(): void
    {
        $optimized = MultiStopHelper::optimizeStopOrder([
            ['type' => 'dropoff', 'address' => 'B', 'latitude' => 3, 'longitude' => 4, 'stop_order' => 3],
            ['type' => 'pickup', 'address' => 'A', 'latitude' => 1, 'longitude' => 2, 'stop_order' => 1],
            ['type' => 'dropoff', 'address' => 'C', 'latitude' => 5, 'longitude' => 6, 'stop_order' => 2],
        ]);

        $this->assertSame([1, 2, 3], array_column($optimized, 'stop_order'));
        $this->assertSame('pickup', $optimized[0]['type']);
    }
}
