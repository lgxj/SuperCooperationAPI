<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function getUserId(){
        $login =  \request()->get('userLogin');
        return $login['user_id'] ?? 0;
    }

    protected function getUserLoginField($field,$default){
        $login = \request()->get('userLogin');
        return $login[$field] ?? $default;
    }
}
