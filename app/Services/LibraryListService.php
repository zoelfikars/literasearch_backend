<?php

namespace App\Services;

use App\Models\Library;
use App\Models\User;
use Illuminate\Http\Request;

class LibraryListService
{
    public function list(Request $request, ?User $user)
    {
        $perPage = (int) $request->get('per_page', 15);
        $order = $request->get('order', 'asc');
        $sort = $request->get('sort', 'id');
        $lat = $request->get('latitude');
        $lng = $request->get('longitude');

        $q = Library::query()
            ->withRatingsAgg()
            ->withCounts()
            ->with([
                'latestApprovedByExpiration:id,library_id,expiration_date',
                'latestPending:id,library_id',
                'librarians:id',
            ]);

        $q->activeFor($user);

        if ($user && ($user->hasRole('Pustakawan Nasional') || $user->hasRole('Super Admin'))) {
            $q->withExists(['latestPending as has_inspection'])
                ->orderByDesc('has_inspection');
        }

        if (!is_null($lat) && !is_null($lng)) {
            $q->orderByDistance((float) $lat, (float) $lng);
        } else {
            $q->orderByAllowed($sort, $order);
        }

        return $q->paginate($perPage);
    }
}
