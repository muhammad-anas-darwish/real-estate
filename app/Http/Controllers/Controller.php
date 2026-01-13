<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponses;
use App\Traits\ApplyPermissions;
use Illuminate\Foundation\Validation\ValidatesRequests;

class Controller extends \Illuminate\Routing\Controller
{
    use ValidatesRequests, ApiResponses, ApplyPermissions;
}
