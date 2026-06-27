<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\ZoneManagement\Entities\Zone;
use Modules\ZoneManagement\Service\Interfaces\ZoneServiceInterface;

class BootstrapDefaultZoneCommand extends Command
{
    protected $signature = 'fleti:bootstrap-zone
                            {--name=Zona Principal : Zone display name}
                            {--force : Create even if an active zone already exists}';

    protected $description = 'Create a default active service zone (São Paulo metro) when none exists';

    public function handle(ZoneServiceInterface $zoneService): int
    {
        $activeCount = Zone::query()->where('is_active', 1)->count();
        if ($activeCount > 0 && ! $this->option('force')) {
            $this->info("Active zones already exist ({$activeCount}). Skipping bootstrap.");
            return self::SUCCESS;
        }

        $name = (string) $this->option('name');
        $existingZone = Zone::withTrashed()->where('name', $name)->first();
        if ($existingZone) {
            if ($existingZone->trashed()) {
                $existingZone->restore();
            }
            if (! $existingZone->is_active) {
                $existingZone->update(['is_active' => 1]);
            }
            $this->info("Zone \"{$name}\" is available (id: {$existingZone->id}).");
            return self::SUCCESS;
        }

        // Greater São Paulo — rough bounding polygon (lat,lng pairs for admin map format)
        $coordinates = '(-23.35,-46.95),(-23.35,-46.35),(-23.75,-46.35),(-23.75,-46.95),(-23.35,-46.95)';

        try {
            $zone = $zoneService->create([
                'name' => $name,
                'coordinates' => $coordinates,
            ]);
            if ($zone && ! $zone->is_active) {
                $zone->update(['is_active' => 1]);
            }
            $this->info("Default zone created: \"{$name}\" (id: {$zone?->id}).");
            $this->line('Adjust boundaries in Admin → Zone Setup → Edit.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to create zone: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
