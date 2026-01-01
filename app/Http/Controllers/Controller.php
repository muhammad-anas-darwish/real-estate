<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponses;
use App\Traits\ApplyPermissions;

abstract class Controller
{
    use ApplyPermissions, ApiResponses;
}
