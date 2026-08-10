<?php

namespace Modules\Ai\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ai\Services\SmartSearchService;

class SmartSearchController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected readonly SmartSearchService $search
    ) {}

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:5|max:500',
        ]);

        $query = trim($request->input('query'));
        $page = max(1, (int) $request->input('page', 1));

        $result = $this->search->search($query, $page);

        return $this->successResponse($result);
    }
}
