<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
class GoogleRouteMatrixService
{
    private string $endpoint = 'https://routes.googleapis.com/distanceMatrix/v2:computeRouteMatrix';
    public function __construct(
        private readonly string $apiKey,
        private readonly int $ttl = 600,
        private readonly int $precision = 3,
        private readonly string $prefix = 'gmaps:rmx:'
    ) {
    }
    public static function makeFromConfig(): self
    {
        $cfg = config('services.google');
        return new self(
            $cfg['maps_key'],
            (int) $cfg['routes_cache_ttl'],
            (int) $cfg['routes_cache_precision'],
            (string) $cfg['routes_cache_prefix'],
        );
    }
    public function distancesForOneOrigin(array $origin, array $destinations, string $routingPreference = 'TRAFFIC_AWARE'): array
    {
        $originCell = $this->cellKey($origin['lat'], $origin['lng'], $this->precision);
        $mode = 'DRIVE';
        $pref = $routingPreference;

        $keyOf = function (int $libId, float $dLat, float $dLng) use ($originCell, $mode, $pref): string {
            return $this->prefix . implode(':', [
                'o',
                $originCell,
                'm',
                $mode,
                'p',
                $pref,
                'd',
                round($dLat, 6) . ',' . round($dLng, 6),
                'id',
                $libId,
            ]);
        };
        $keys = [];
        foreach ($destinations as $d) {
            $keys[$d['library_id']] = $keyOf((int) $d['library_id'], (float) $d['lat'], (float) $d['lng']);
        }

        $cached = Cache::many(array_values($keys));
        $result = [];
        $misses = [];

        foreach ($destinations as $d) {
            $libId = (int) $d['library_id'];
            $k = $keys[$libId];
            if (array_key_exists($k, $cached) && $cached[$k] !== null) {
                $result[$libId] = (int) $cached[$k];
            } else {
                $misses[] = $d;
            }
        }

        if (empty($misses)) {
            return $result;
        }

        $chunkSize = 625;
        foreach (array_chunk($misses, $chunkSize) as $chunk) {
            $payload = [
                'origins' => [
                    [
                        'waypoint' => [
                            'location' => [
                                'latLng' => [
                                    'latitude' => $origin['lat'],
                                    'longitude' => $origin['lng'],
                                ]
                            ]
                        ]
                    ]
                ],
                'destinations' => array_values(array_map(function ($d) {
                    return [
                        'waypoint' => [
                            'location' => [
                                'latLng' => [
                                    'latitude' => (float) $d['lat'],
                                    'longitude' => (float) $d['lng'],
                                ]
                            ]
                        ]
                    ];
                }, $chunk)),
                'travelMode' => $mode,
                'routingPreference' => $pref,
            ];
            $resp = Http::withHeaders([
                'X-Goog-Api-Key' => $this->apiKey,
                'X-Goog-FieldMask' => 'originIndex,destinationIndex,distanceMeters,status',
            ])->post($this->endpoint, $payload);
            if (!$resp->ok()) {
                \Log::warning('Routes API error', [
                    'status' => $resp->status(),
                    'body' => $resp->body(),
                ]);
                foreach ($chunk as $d) {
                    $result[(int) $d['library_id']] = null;
                }
                continue;
            }
            $idByDestIndex = [];
            foreach (array_values($chunk) as $idx => $d) {
                $idByDestIndex[$idx] = (int) $d['library_id'];
            }
            if (!$resp->ok()) {
                foreach ($chunk as $d) {
                    $libId = (int) $d['library_id'];
                    $result[$libId] = $result[$libId] ?? null;
                }
                continue;
            }
            $elements = $resp->json();
            foreach ($elements as $el) {
                $libId = $idByDestIndex[$el['destinationIndex']] ?? null;
                if ($libId === null)
                    continue;
                $dist = null;
                if (empty($el['status']) || ($el['status']['code'] ?? 0) === 0) {
                    $dist = isset($el['distanceMeters']) ? (int) $el['distanceMeters'] : null;
                }

                $result[$libId] = $dist;

                if (!is_null($dist)) {
                    $jitter = random_int(0, 60);
                    Cache::put($keys[$libId], $dist, now()->addSeconds($this->ttl + $jitter));
                }
            }
        }
        return $result;
    }
    private function cellKey(float $lat, float $lng, int $precision): string
    {
        return sprintf('%s,%s', round($lat, $precision), round($lng, $precision));
    }
}
