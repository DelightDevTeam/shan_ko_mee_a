<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    protected function ifChildOfParent($prent_id, $child_id)
    {
        return User::where('agent_id', $prent_id)->where('id', $child_id)->exists();
    }
}
