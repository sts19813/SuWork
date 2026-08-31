<?php

namespace App\Console\Commands;

use App\Models\Property;
use App\Services\PropertyMapLocationResolver;
use Illuminate\Console\Command;

class SyncPropertyMapLocationsCommand extends Command
{
    protected $signature = 'properties:sync-map-locations {--force : Reprocesa coordenadas aunque ya existan}';

    protected $description = 'Extrae coordenadas desde los links de Google Maps guardados en propiedades.';

    public function handle(PropertyMapLocationResolver $resolver): int
    {
        $query = Property::query()
            ->whereNotNull('map_url')
            ->where('map_url', '<>', '');

        if (! $this->option('force')) {
            $query->where(function ($query): void {
                $query
                    ->whereNull('map_latitude')
                    ->orWhereNull('map_longitude');
            });
        }

        $total = (clone $query)->count();
        $resolved = 0;
        $failed = 0;

        $this->info("Propiedades por resolver: {$total}");

        $query
            ->orderBy('id')
            ->chunkById(25, function ($properties) use ($resolver, &$resolved, &$failed): void {
                foreach ($properties as $property) {
                    $location = $resolver->resolve((string) $property->map_url);

                    if ($location === null) {
                        $failed++;
                        $this->warn("Sin coordenadas: {$property->internal_name}");

                        continue;
                    }

                    $property->forceFill([
                        'map_latitude' => $location['latitude'],
                        'map_longitude' => $location['longitude'],
                        'map_resolved_url' => $location['resolved_url'],
                        'map_coordinates_resolved_at' => now(),
                    ])->save();

                    $resolved++;
                    $this->line("OK {$property->internal_name}: {$location['latitude']}, {$location['longitude']}");
                }
            });

        $this->info("Coordenadas actualizadas: {$resolved}. Sin resolver: {$failed}.");

        return self::SUCCESS;
    }
}
