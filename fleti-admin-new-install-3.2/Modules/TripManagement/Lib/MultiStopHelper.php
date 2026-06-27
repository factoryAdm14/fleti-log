<?php

namespace Modules\TripManagement\Lib;

use InvalidArgumentException;

class MultiStopHelper
{
    public static function isEnabled(): bool
    {
        return (bool) (businessConfig('enable_multi_stop_delivery', PARCEL_SETTINGS)?->value ?? 0);
    }

    public static function maxStops(): int
    {
        return (int) (businessConfig('multi_stop_max_stops', PARCEL_SETTINGS)?->value ?? 20);
    }

    public static function parseStops(mixed $stops): array
    {
        if (is_string($stops)) {
            $stops = json_decode($stops, true);
        }

        if (!is_array($stops)) {
            throw new InvalidArgumentException('Stops must be a valid JSON array.');
        }

        return $stops;
    }

    public static function validateStops(array $stops): void
    {
        $max = self::maxStops();
        $count = count($stops);

        if ($count < 2) {
            throw new InvalidArgumentException('Multi-stop delivery requires at least 2 stops.');
        }

        if ($count > $max) {
            throw new InvalidArgumentException("Multi-stop delivery allows up to {$max} stops.");
        }

        $hasPickup = false;
        $hasDropoff = false;

        foreach ($stops as $index => $stop) {
            if (!isset($stop['type'], $stop['address'], $stop['latitude'], $stop['longitude'])) {
                throw new InvalidArgumentException('Each stop requires type, address, latitude and longitude.');
            }

            if (!in_array($stop['type'], ['pickup', 'dropoff'], true)) {
                throw new InvalidArgumentException('Stop type must be pickup or dropoff.');
            }

            $stop['type'] === 'pickup' ? $hasPickup = true : $hasDropoff = true;
            $stops[$index]['stop_order'] = $stop['stop_order'] ?? ($index + 1);
        }

        if (!$hasPickup || !$hasDropoff) {
            throw new InvalidArgumentException('Multi-stop delivery requires at least one pickup and one dropoff.');
        }
    }

    public static function optimizeStopOrder(array $stops): array
    {
        usort($stops, fn ($a, $b) => ($a['stop_order'] ?? 0) <=> ($b['stop_order'] ?? 0));

        foreach ($stops as $index => $stop) {
            $stops[$index]['stop_order'] = $index + 1;
        }

        return $stops;
    }
}
