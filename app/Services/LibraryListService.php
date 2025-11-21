<?php
namespace App\Services;
use App\Http\Requests\LibraryFilterRequest;
use App\Models\Edition;
use App\Models\Library;
use App\Models\User;
class LibraryListService
{
    public function __construct(private readonly OpenRouteServiceMatrixService $ors)
    {
    }
    public function list(LibraryFilterRequest $request, ?User $user, ?Edition $book = null)
    {
        $perPage = (int) $request->get('per_page', 15);
        $order = strtolower($request->get('order', 'desc')) === 'desc' ? 'desc' : 'asc';
        $sort = $request->get('sort', 'id');
        $lat = (float) $request->get('latitude', 0);
        $lng = (float) $request->get('longitude', 0);
        $editionId = $book?->id;

        $q = Library::query()
            ->select('libraries.*')
            ->withRatingsAgg()
            ->withCounts()
            ->with([
                'latestApprovedByExpiration',
                'latestPending',
                'librarians',
            ])
            ->withCount('editions as editions_count')
            ->withSum('libraryEditions as physical_stock_total', 'stock_total')
            ->withDistance($lat, $lng)
            ->activeFor($user)
            ->search($request->get('search'));

        if ($user) {
            $q->withExists(['latestPending as has_inspection']);
            $q->withExists([
                'librarians as is_librarian_exists' => fn($qq) => $qq->where('users.id', $user->id)
            ]);
        }

        if ($editionId) {
            $q->whereHasEdition($editionId);
            $q->with([
                'editions' => fn($qq) =>
                    $qq->select('editions.id')->where('editions.id', $editionId)->withPivot('stock_total')
            ]);
            $q->withSum(
                ['libraryEditions as selected_stock_total' => fn($qq) => $qq->where('edition_id', $editionId)],
                'stock_total'
            );
        }

        if ($sort === 'road_distance') {
            $maxCandCfg = (int) config('services.ors.max_candidates', 200);
            $maxCandReq = (int) $request->get('max_candidates', $maxCandCfg);
            $maxCand = max(1, min($maxCandReq, 3500));

            // Ambil kandidat (pakai scope)
            $candidates = (clone $q)
                ->limitHaversineCandidates($maxCand)
                ->get(['libraries.id', 'libraries.latitude', 'libraries.longitude']);

            if ($candidates->isEmpty()) {
                return $q->applySort('distance', $order)->paginate($perPage);
            }

            $destinations = $candidates->map(fn($lib) => [
                'library_id' => (string) $lib->id,
                'lat' => (float) $lib->latitude,
                'lng' => (float) $lib->longitude,
            ])->values()->all();

            $distMap = $this->ors->distancesForOneOrigin(['lat' => $lat, 'lng' => $lng], $destinations);

            $sortedIds = collect($distMap)->sort(function ($a, $b) use ($order) {
                if ($a === null && $b === null)
                    return 0;
                if ($a === null)
                    return 1;
                if ($b === null)
                    return -1;
                return $order === 'asc' ? $a <=> $b : $b <=> $a;
            })->keys()->values()->all();

            if (!$sortedIds) {
                return $q->applySort('distance', $order)->paginate($perPage);
            }

            app()->instance('lib.road_distance.map', $distMap);

            // Urutan final (pakai scope)
            $q->applyOrderByIds($sortedIds, $order);
            return $q->paginate($perPage);
        }

        // Sort lain cukup satu baris
        $q->applySort($sort, $order, [
            'edition_id' => $editionId,
            'has_is_librarian_exists' => (bool) $user,
        ]);

        return $q->paginate($perPage);
    }
}
