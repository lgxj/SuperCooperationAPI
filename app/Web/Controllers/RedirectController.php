<?php


namespace App\Web\Controllers;


use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;

class RedirectController extends ScController
{

    /**
     * @param Request $request
     * @return RedirectResponse|Redirector|string
     * @throws InvalidArgumentException
     */
    public function redirectHorizon(Request $request){
        $token = $request->get('token','');
        if($token == 'eafai7ybc892ab2af'){
            Cache::set('horizon_visit',1,86400);
        }else{
            Cache::set('horizon_visit',0);
            return '403 forbidden';
        }
        return redirect('horizon');
    }

    /**
     * @param Request $request
     * @return RedirectResponse|Redirector|string
     * @throws InvalidArgumentException
     */
    public function redirectLogView(Request $request){
        $token = $request->get('token','');
        if($token == 'eafai7ybc892ab2af'){
            Cache::set('view_visit',1,86400);
        }else{
            Cache::set('view_visit',0);
            return '403 forbidden';
        }
        return redirect('log-viewer');
    }
}
