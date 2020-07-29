<?php


namespace App\Admin\Controllers\Statistics;


use App\Admin\Controllers\ScController;
use App\Bridges\Statistics\TaskDayBridge;
use App\Services\Statistics\TaskDayService;
use Illuminate\Http\Request;

class IndexController extends ScController
{
    /**
     * @var TaskDayService
     */
    protected $taskDayBridge;

    public function __construct(TaskDayBridge $taskDayBridge)
    {
        $this->taskDayBridge = $taskDayBridge;
    }

    public function taskDay(Request $request){
        $default = date('Ymd');
        $day = $request->get('day',$default);
        $statics = $this->taskDayBridge->getDay($day);
        return success($statics);
    }

    public function taskDayList(Request $request){
        $filter = $request->input('filter');
        $filter = json_decode($filter, true);
        $pageSize = $request->input('limit');
        $result = $this->taskDayBridge->search($filter,$pageSize);
        return success(formatPaginate($result));
    }
}
