<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
class OpenRouteServiceMatrixService
{
    private string $endpoint = 'https://api.openrouteservice.org/v2/matrix/driving-car';
    public function __construct(
        private readonly string $apiKey,
        private readonly int $ttl = 600,
        private readonly int $precision = 3,
        private readonly string $prefix = 'ors:rmx:'
    ) {
    }
    public static function makeFromConfig(): self
    {
        $cfg = config('services.ors');
        return new self(
            $cfg['api_key'],
            (int) $cfg['cache_ttl'],
            (int) $cfg['precision'],
            (string) $cfg['cache_prefix']
        );
    }
    public function distancesForOneOrigin(array $origin, array $destinations): array
    {
        $originCell = $this->cellKey($origin['lat'], $origin['lng'], $this->precision);
        $keyOf = function (string $libId, float $dLat, float $dLng) use ($originCell): string {
            return $this->prefix . implode(':', [
                'o',
                $originCell,
                'p',
                'driving-car',
                'd',
                round($dLat, 6) . ',' . round($dLng, 6),
                'id',
                $libId,
            ]);
        };
        $keys = [];
        foreach ($destinations as $d) {
            $libId = (string) $d['library_id'];
            $keys[$libId] = $keyOf($libId, (float) $d['lat'], (float) $d['lng']);
        }
        $cached = Cache::many(array_values($keys));
        $result = [];
        $misses = [];
        foreach ($destinations as $d) {
            $libId = (string) $d['library_id'];
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
        $chunkSize = 3500;
        foreach (array_chunk($misses, $chunkSize) as $chunk) {
            $locations = [];
            $locations[] = [(float) $origin['lng'], (float) $origin['lat']];
            foreach ($chunk as $d) {
                $locations[] = [(float) $d['lng'], (float) $d['lat']];
            }
            $destIdx = range(1, count($chunk));
            $payload = [
                'locations' => $locations,
                'sources' => [0],
                'destinations' => $destIdx,
                'metrics' => ['distance'],
            ];
            $resp = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->endpoint, $payload);
            $idByDestIndex = [];
            foreach (array_values($chunk) as $i => $d) {
                $idByDestIndex[$i + 1] = (string) $d['library_id'];
            }
            if (!$resp->ok()) {
                \Log::warning('ORS Matrix error', [
                    'status' => $resp->status(),
                    'body' => $resp->body(),
                ]);
                foreach ($chunk as $d) {
                    $libId = (string) $d['library_id'];
                    $result[$libId] = $result[$libId] ?? null;
                }
                continue;
            }

            $json = $resp->json();
            $distRow = $json['distances'][0] ?? [];
            if (!is_array($distRow)) {
                \Log::warning('ORS Matrix: distances[0] missing/invalid', ['distances' => $json['distances'] ?? null]);
                foreach ($chunk as $d) {
                    $result[(string) $d['library_id']] = $result[(string) $d['library_id']] ?? null;
                }
                continue;
            }
            foreach (range(1, count($chunk)) as $i) {
                $libId = $idByDestIndex[$i] ?? null;
                if ($libId === null)
                    continue;
                $pos = $i - 1;
                $val = $distRow[$pos] ?? null;
                $dist = is_numeric($val) ? (int) $val : null;
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
