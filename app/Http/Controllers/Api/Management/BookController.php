<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class BookController extends Controller
{
    use AuthorizesRequests;
    public function store(StoreBookRequest $request, $book)
    {
        $user = auth()->user();
        $this->authorize('storeBook', $user);
    }
}
