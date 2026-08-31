<?php

namespace App\Services;

use GuzzleHttp\TransferStats;
use Illuminate\Support\Facades\Http;
use Throwable;

class PropertyMapLocationResolver
{
    public function resolve(string $mapUrl): ?array
    {
        $mapUrl = trim($mapUrl);

        if ($mapUrl === '') {
            return null;
        }

        $coordinates = $this->extractCoordinates($mapUrl);

        if ($coordinates !== null) {
            return [
                ...$coordinates,
                'resolved_url' => $mapUrl,
            ];
        }

        $resolved = $this->resolveRedirect($mapUrl);

        if ($resolved === null || $resolved['url'] === $mapUrl) {
            return null;
        }

        $resolvedUrl = $resolved['url'];
        $coordinates = $this->extractCoordinates($resolvedUrl);

        if ($coordinates === null) {
            $coordinates = $this->extractCoordinates($resolved['body']);
        }

        if ($coordinates === null) {
            return null;
        }

        return [
            ...$coordinates,
            'resolved_url' => $resolvedUrl,
        ];
    }

    public function extractCoordinates(string $url): ?array
    {
        $decodedUrl = urldecode($url);
        $patterns = [
            '/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/',
            '/@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/',
            '/\/place\/(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/',
            '/\/maps\/search\/(-?\d+(?:\.\d+)?),\s*\+?(-?\d+(?:\.\d+)?)/',
            '/[?&](?:q|ll)=(-?\d+(?:\.\d+)?),\s*\+?(-?\d+(?:\.\d+)?)/',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $decodedUrl, $matches)) {
                continue;
            }

            $latitude = (float) $matches[1];
            $longitude = (float) $matches[2];

            if ($this->isValidCoordinate($latitude, $longitude)) {
                return [
                    'latitude' => round($latitude, 7),
                    'longitude' => round($longitude, 7),
                ];
            }
        }

        return null;
    }

    private function resolveRedirect(string $mapUrl): ?array
    {
        $effectiveUrl = null;

        try {
            $response = Http::timeout(8)
                ->connectTimeout(5)
                ->withHeaders([
                    'User-Agent' => 'SuHomes local map resolver',
                ])
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 8,
                        'strict' => false,
                        'referer' => true,
                    ],
                    'on_stats' => function (TransferStats $stats) use (&$effectiveUrl): void {
                        $effectiveUrl = (string) $stats->getEffectiveUri();
                    },
                ])
                ->get($mapUrl);
        } catch (Throwable) {
            return null;
        }

        if ($effectiveUrl === null) {
            return null;
        }

        return [
            'url' => $effectiveUrl,
            'body' => $response->body(),
        ];
    }

    private function isValidCoordinate(float $latitude, float $longitude): bool
    {
        return $latitude >= -90
            && $latitude <= 90
            && $longitude >= -180
            && $longitude <= 180;
    }
}
