<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class LibraryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $avg = (float) ($this->ratings_avg_rating ?? 0);
        $count = (int) ($this->ratings_count ?? 0);
        // $userId = $request->user('sanctum')?->id;
        // $isLibrarian = $this->relationLoaded('librarians') && $userId
        //     ? $this->librarians->pluck('id')->contains($userId)
        //     : (bool) ($this->is_librarian_exists ?? false);
        $roadMap = app()->bound('lib.road_distance.map') ? app('lib.road_distance.map') : null;
        $roadMeters = is_array($roadMap) ? ($roadMap[$this->id] ?? null) : null;
        $originLat = (float) $request->get('latitude');
        $originLng = (float) $request->get('longitude');
        $destLat = (float) $this->latitude;
        $destLng = (float) $this->longitude;
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone_number,
            'address' => $this->address,
            'rating' => [
                'avg' => round($avg, 2),
                'count' => $count,
            ],
            'stock' => [
                'physical_total' => (int) ($this->physical_stock_total ?? 0),
                'titles_count' => (int) ($this->editions_count ?? 0),
                'this_edition' => isset($this->selected_stock_total)
                    ? (int) $this->selected_stock_total
                    : ($this->relationLoaded('editions') && $this->editions->isNotEmpty()
                        ? (int) ($this->editions->first()->pivot->stock_total ?? 0)
                        : null),
            ],
            'distances' => [
                'straight_km' => format_distance($this->distance), // hasil selectRaw haversine km
                'route_m' => $roadMeters,             // dari app('lib.road_distance.map')
                'route_text' => $roadMeters !== null ? format_distance($roadMeters / 1000) : null,
            ],
            'actions' => [
                'maps_place_url' => "https://www.google.com/maps/search/?api=1&query={$destLat},{$destLng}",
                'maps_directions_url' => "https://www.google.com/maps/dir/?api=1&origin={$originLat},{$originLng}&destination={$destLat},{$destLng}&travelmode=driving",
                'android_intent' => "google.navigation:q={$destLat},{$destLng}&mode=d",
                'geo_uri' => "geo:{$destLat},{$destLng}?q={$destLat},{$destLng}(" . rawurlencode($this->name) . ")",
            ],
            'inspection_id' => ($this->has_inspection ?? false)
                ? ($this->relationLoaded('latestPending') ? $this->latestPending->id : null)
                : null,
            // 'is_librarian' => (bool) $isLibrarian,
        ];
    }
}
