<?php

namespace Modules\Communication\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Communication\Services\ChatService;

class TypingController extends Controller
{
    public function __construct(
        protected readonly ChatService $chatService
    ) {}

    public function store(int $roomId)
    {
        return response()->json(['success' => true, 'message' => 'typing event broadcasted']);
    }
}
