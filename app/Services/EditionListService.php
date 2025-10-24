<?php
namespace App\Services;
use App\Http\Requests\EditionFilterRequest;
use App\Models\Edition;
use App\Models\Library;
use App\Models\Loan;
use Auth;
use DB;
class EditionListService
{
    public function list(EditionFilterRequest $request, ?Library $library = null)
    {
        $per_page = (int) $request->get('per_page', 15);
        $sort = $request->get('sort', 'created_at');
        $order = strtolower($request->get('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = $request->get('search');
        $library_id = $library?->id ?? $request->get('library_id') ?? null;
        $filters = [
            'title_id' => $request->get('title_id'),
            'publisher_id' => $request->get('publisher_id'),
            'language_id' => $request->get('language_id'),
            'author_id' => $request->get('author_id'),
            'role_id' => $request->get('role_id'),
            'subject_ids' => $request->get('subject_ids', []),
        ];
        $q = Edition::query()
            ->with([
                'title:id,title as name',
                'publisher:id,name',
                'language:id,english_name,native_name,iso_639_1,iso_639_3',
                'writers',
                'subjects:id,name',
                'ratingRecords',
                'libraries' => function ($l) use ($library_id) {
                    if ($library_id) {
                        $l->select('libraries.id', 'libraries.name')
                            ->where('libraries.id', $library_id);
                    } else {
                        $l->select('libraries.id', 'libraries.name');
                    }
                },
            ])
            ->withRatingsAgg()
            ->when($search, fn($qq) => $qq->search($search))
            ->filter($filters)
            ->when($library_id, fn($qq, $id) => $qq->inLibrary($id))
            ->withCount([
                'loans as active_loans' => function ($l) {
                    $l->loanStatus('approved')
                        ->whereNull('returned_at');
                },
            ])
            ->when($library_id, function ($qq) use ($library_id) {
                $qq->withCount([
                    'loans as active_loans_in_library' => function ($l) use ($library_id) {
                        $l->where('library_id', $library_id)
                            ->loanStatus('approved')
                            ->whereNull('returned_at');
                    },
                ]);
            });
        $userId = Auth::guard('sanctum')->id();
        if ($userId) {
            $q->leftJoin('edition_read_positions as erp', function ($j) use ($userId) {
                $j->on('erp.edition_id', '=', 'editions.id')
                    ->where('erp.user_id', '=', $userId);
            });
            $q->addSelect([
                'editions.*',
                DB::raw("
                    ROUND(
                        LEAST(
                            100,
                            CASE
                                WHEN erp.locator_type = 'page'
                                     AND erp.page IS NOT NULL
                                     AND editions.pages > 0
                                THEN (erp.page / editions.pages) * 100
                                WHEN erp.locator_type = 'cfi'
                                     AND erp.progress_percent IS NOT NULL
                                THEN erp.progress_percent
                                ELSE 0
                            END
                        ), 2
                    ) as user_read_progress
                "),
                DB::raw('erp.locator_type as user_locator_type'),
                DB::raw('erp.page as user_read_page'),
                DB::raw('erp.progress_percent as user_read_percent'),
                DB::raw('erp.cfi as user_read_cfi'),
                DB::raw('erp.last_opened_at as user_last_opened_at'),
            ]);
        }
        $userId = Auth::guard('sanctum')->id();

        if ($userId) {
            $q->leftJoin('edition_wishlists as ewl', function ($j) use ($userId) {
                $j->on('ewl.edition_id', '=', 'editions.id')
                    ->where('ewl.user_id', '=', $userId);
            });
            $q->addSelect('editions.*');
            $q->addSelect([
                DB::raw('CASE WHEN ewl.user_id IS NULL THEN 0 ELSE 1 END as is_wishlisted'),
            ]);
        } else {
            $q->addSelect('editions.*');
            $q->addSelect([
                DB::raw('NULL as is_wishlisted'),
                // DB::raw('NULL as user_read_progress'),
                // DB::raw('NULL as user_locator_type'),
                // DB::raw('NULL as user_read_page'),
                // DB::raw('NULL as user_read_percent'),
                // DB::raw('NULL as user_read_cfi'),
                // DB::raw('NULL as user_last_opened_at'),
            ]);
        }
        $q->orderByAllowed($sort, $order, $library_id);
        return $q->paginate($per_page);
    }
}
