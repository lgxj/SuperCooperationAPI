<?php


namespace App\Http\Controllers\Common;


use App\Http\Controllers\Controller;
use App\Services\Common\UpgradeService;
use Illuminate\Http\Request;

class UpgradeController extends Controller
{
    protected $upgradeService;

    public function __construct(UpgradeService $upgradeService)
    {
        $this->upgradeService = $upgradeService;
    }

    public function index(Request $request){
        $appName = $request->get('app_name','全民帮帮');
        $appType = $request->get('app_type','');
        $version = $request->get('version',0);
        $latestVersion = $this->upgradeService->getLatestVersion($appName,$appType,$version);
        return success($latestVersion);
    }
}
