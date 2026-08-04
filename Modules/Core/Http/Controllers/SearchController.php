<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Core\Services\SearchService;

class SearchController extends Controller
{
    public function __construct(protected readonly SearchService $searchService) {}

    public function search(string $method)
    {
        return $this->successResponse($this->searchService->call($method));
    }
}
