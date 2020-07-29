<?php


namespace App\Http\Controllers\Trade\Order;


use App\Consts\GlobalConst;
use App\Http\Controllers\Controller;
use App\Services\Trade\Order\Helper\SearchService;
use App\Utils\Map\AMap;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * @var SearchService
     */
    protected $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function options(Request $request){
       return success($this->searchService->getSearchOptions());
    }

    public function search(Request $request){
        $orderName = $request->get('order_name','');
        $distance = $request->get('distance',10);
        $page = $request->get('page',1);
        $pageSize = $request->get('page_size',GlobalConst::PAGE_SIZE);
        $searchType = $request->get('search_type',[]);
        $orderCategory = $request->get('order_category',[]);
        $sort = $request->get('sort',0);
        $lng = $request->get('lng','');
        $lat = $request->get('lat','');
        if(empty($lng) || empty($lat)){
            $ip = $request->ip();
            $ip = ($ip == '127.0.0.1' ? '111.181.187.237' : $ip);
            $amp = new AMap();
            $altitude = $amp->getAltitudeByIp($ip);
            $altitude = isset($altitude['rectangle']) ? explode(',',$altitude['rectangle'][0]) : [];
            $lng = $altitude[0] ?? '';
            $lat = $altitude[1] ?? '';
        }
        $searchType =  $searchType ?: [];
        $orderCategory =  $orderCategory ?: [];
        $sort = $sort ?: 0;
        if($distance >= 10){
            $distance = 10;
        }
        return success($this->searchService->search($orderName,$lng,$lat,$distance,$searchType,$orderCategory,$page,$sort,$pageSize,getLoginUserId()));
    }

    public function nearByTask(Request $request){
        $distance = $request->get('distance',10);
        $page = $request->get('page',1);
        $pageSize = $request->get('page_size',99);
        $lng = $request->get('lng','');
        $lat = $request->get('lat','');
        if(empty($lng) || empty($lat)){
            $ip = $request->ip();
            $ip = ($ip == '127.0.0.1' ? '111.181.187.237' : $ip);
            $amp = new AMap();
            $altitude = $amp->getAltitudeByIp($ip);
            $altitude = isset($altitude['rectangle']) ? explode(',',$altitude['rectangle'][0]) : [];
            $lng = $altitude[0] ?? '';
            $lat = $altitude[1] ?? '';
        }
        if($distance >= 10){
            $distance = 10;
        }
        return success($this->searchService->search('',$lng,$lat,10,[],[],$page,0,$pageSize));
    }

    public function nearByHelper(Request $request){
        $distance = $request->get('distance',10);
        $page = $request->get('page',1);
        $pageSize = $request->get('page_size',GlobalConst::PAGE_SIZE);
        $lng = $request->get('lng','');
        $lat = $request->get('lat','');
        if(empty($lng) || empty($lat)){
            $ip = $request->ip();
            $ip = ($ip == '127.0.0.1' ? '111.181.187.237' : $ip);
            $amp = new AMap();
            $altitude = $amp->getAltitudeByIp($ip);
            $altitude = isset($altitude['rectangle']) ? explode(',',$altitude['rectangle'][0]) : [];
            $lng = $altitude[0] ?? '';
            $lat = $altitude[1] ?? '';
        }
        return success($this->searchService->searchHelper($lng,$lat,10,$page,$pageSize));
    }

}
