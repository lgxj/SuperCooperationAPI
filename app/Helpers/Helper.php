<?php

use App\Consts\MessageConst;
use App\Consts\Trade\OrderConst;
use App\Consts\UploadFileConst;
use App\Consts\UserConst;
use App\Exceptions\BusinessException;
use App\Services\Message\NoticeMessageService;
use App\Services\Message\OrderMessageService;
use App\Services\Message\PushService;
use App\Services\Trade\Order\Employer\DetailTaskOrderService;
use App\Utils\Map\AMap;
use App\Utils\Map\TencentYunTu;
use App\Utils\Map\YunTu;
use App\Utils\Map\YunTuInterface;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

if (!function_exists('getSubId')) {
    function getSubId()
    {
        return request()->header('SC-SUB-ID', 0);
    }
}

if (!function_exists('gmt_iso8601')) {
    function gmt_iso8601($time)
    {
        $dtStr = date("c", $time);
        $mydatetime = new DateTime($dtStr);
        $expiration = $mydatetime->format(DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration . "Z";
    }
}

if (!function_exists('showArticleField')) {
    /**
     * 文章字段是否显示判断
     * @param $field
     * @param $fields
     * @return bool
     */
    function articleShowField($field, $fields)
    {
        if (is_array($field)) {
            return count(array_intersect($field, $fields)) > 0;
        } else {
            return in_array($field, $fields);
        }
    }
}

if (!function_exists('formatPaginate')) {
    /**
     * 文件网络路径
     * @param $path
     * @return string
     */
    function getFullPath($path) {
        return strpos($path, 'http') === 0 ? $path : (env('APP_ADMIN_API_URL') . '/' . $path);
    }
}

if (!function_exists('formatPaginate')) {
    /**
     * 格式化Paginate分页数据
     * @param LengthAwarePaginator $paginator
     * @return array
     */
    function formatPaginate(LengthAwarePaginator $paginator) {
        return [
            'total' => $paginator->total(),
            'list' => $paginator->items()
        ];
    }
}

if (!function_exists('out')) {
    /**
     * @param int    $code
     * @param string $msg
     * @param bool   $success
     * @param array  $data
     * @return \Illuminate\Http\JsonResponse
     */
    function out(
        int $code,
        string $msg = 'success',
        bool $success = true,
        array $data = []
    ): \Illuminate\Http\JsonResponse {
        $data = (empty($data) && !is_array($data)) ? (object)$data : $data;
        return response()->json([
            'code' => $code,
            'message' => $msg,
            'success' => $success,
            'data' => $data,
        ]);
    }
}

if (!function_exists('success')) {
    /**
     * @param array  $data
     * @param string $msg
     * @param int    $code
     * @return \Illuminate\Http\JsonResponse
     */
    function success(
        array $data = [],
        string $msg = \App\Consts\GlobalConst::SUCCESS_MSG,
        int $code = \App\Consts\GlobalConst::SUCCESS
    ): \Illuminate\Http\JsonResponse {
        return out($code, $msg, true, $data);
    }
}

if (!function_exists('fail')) {
    /**
     * @param array  $data
     * @param string $msg
     * @param int    $code
     * @return \Illuminate\Http\JsonResponse
     */
    function fail(
        array $data = [],
        string $msg = \App\Consts\GlobalConst::FAIL_MSG,
        int $code = \App\Consts\GlobalConst::FAIL
    ): \Illuminate\Http\JsonResponse {
        return out($code, $msg, false, $data);
    }
}

if (!function_exists('quick_random')) {
    /**
     * @param int $length
     * @return bool|string
     */
    function quick_random($length = 16)
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
    }
}

if (!function_exists('validate_errors')) {
    function validate_errors(\Illuminate\Support\MessageBag $errors)
    {
        $msgs = $errors->getMessages();
        foreach ($msgs as &$msg) {
            $msg = implode(' ', $msg);
        }
        return implode(' ', $msgs);
    }
}

if (!function_exists('formatter_input')) {
    /**
     * @param \Illuminate\Http\Request $request
     * @param string                   $variableType
     * @param string                   $key
     * @param                          $default
     * @throws Exception
     */
    function formatter_input(\Illuminate\Http\Request $request, string $variableType, string $key, $default)
    {
        if (!$request->filled($key)) {
            $value = $default;
        } else {
            $value = $request->input($key, $default);
            $result = settype($value, $variableType);
            if (!$result) {
                throw new Exception();
            }
        }
        $request->$key = $value;
    }
}

if (!function_exists('formatter_inputs')) {
    /**
     * @param \Illuminate\Http\Request $request
     * @param array                    $configs
     * @return mixed
     * @throws Exception
     */
    function formatter_inputs(\Illuminate\Http\Request $request, array $configs)
    {
        foreach ($configs as $config) {
            list($variableType, $key, $default) = $config;
            formatter_input($request, $variableType, $key, $default);
        }
    }
}

if (!function_exists('json_encode_clean')) {
    /**
     * @param $array
     * @return false|string
     */
    function json_encode_clean($array)
    {
        return json_encode($array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}


function parseActionName()
{
    list($class, $method) = explode('@', request()->route()->getActionName());
    $module = str_replace(
        '\\',
        '.',
        str_replace(
            'App\\Http\\Controllers\\',
            '',
            trim(
                implode('\\', array_slice(explode('\\', $class), 0, -1)),
                '\\'
            )
        )
    );

    $controller = str_replace(
        'Controller',
        '',
        substr(strrchr($class, '\\'), 1)
    );
   // $module = strtolower($module);
   // $controller = strtolower($controller);
   // $method = strtolower($method);
    return [$module,$controller,$method];
}


function getBankNameByCardNo($card)
{
    $bankList = config('banklist');
    $card_9 = substr($card, 0, 9);
    if (isset($bankList[$card_9])) {
        return $bankList[$card_9];
    }

    $card_8 = substr($card, 0, 8);
    if (isset($bankList[$card_8])) {
        return $bankList[$card_8];
    }
    $card_6 = substr($card, 0, 6);
    if (isset($bankList[$card_6])) {
        return $bankList[$card_6];
    }
    $card_5 = substr($card, 0, 5);
    if (isset($bankList[$card_5])) {
        return $bankList[$card_5];
    }
    $card_4 = substr($card, 0, 4);
    if (isset($bankList[$card_4])) {
        return $bankList[$card_4];
    }
    Log::error("user bank card no error:{$card}");
    return '';
}

/**
 * @param \Illuminate\Http\UploadedFile $file
 * @param $directory
 * @param int $businessType
 * @return array
 * @throws BusinessException
 */
function uploadFileCheck(\Illuminate\Http\UploadedFile $file,$directory,$businessType = 0){

    $allowBusinessSize = [
        0 => 10*1024*1024
    ];
    $allowExt = ['jpg','jpeg','png','txt','mp4','tif','xls','csv'];
    $originExt = $file->getClientOriginalExtension();
    $originSize = $file->getSize();
    if(!in_array($originExt,$allowExt)){
        throw new BusinessException("文件类型错误:{$originExt}");
    }
    $allowSize = $allowBusinessSize[$businessType];

    if($originSize > $allowSize){
        throw new BusinessException("文件太大:{$originSize}");
    }
    $fileName = date('YmdHis').quick_random('8').'.'.$file->getClientOriginalExtension();
    $newFile = $file->move($directory,$fileName);
    return [$newFile->getPath().DIRECTORY_SEPARATOR.$newFile->getBasename(),$newFile->getRealPath()];
}

/**
 * @param \Illuminate\Http\UploadedFile $file
 * @param $directory
 * @param int $businessType
 * @return array
 * @throws BusinessException
 */
function getFileContent(\Illuminate\Http\UploadedFile $file,$directory,$businessType = UploadFileConst::BUSINESS_TYPE_GENERAL){

    $allowBusinessSize = [
        UploadFileConst::BUSINESS_TYPE_GENERAL => 10*1024*1024,
        UploadFileConst::BUSINESS_TYPE_PACKAGE => 50*1024*1024
    ];
    $allowExts =[
        UploadFileConst::BUSINESS_TYPE_GENERAL => ['jpg','jpeg','png','txt','mp4','tif','xls','csv','webm','ogg','3gp','mp3','acc'],
        UploadFileConst::BUSINESS_TYPE_PACKAGE => ['wgt','ipa','apk']
    ];
    $allowExt = $allowExts[$businessType] ?? [];
    $originExt = strtolower($file->getClientOriginalExtension());
    $originSize = $file->getSize();
    if(!in_array($originExt,$allowExt)){
        throw new BusinessException("文件类型错误:{$originExt}");
    }
    $allowSize = $allowBusinessSize[$businessType];

    if($originSize > $allowSize){
        throw new BusinessException("文件太大:{$originSize}");
    }
    $fileName = date('YmdHis').quick_random('8').'.'.$file->getClientOriginalExtension();
    $content = file_get_contents($file->getRealPath());
    return [$directory.$fileName,$content];
}


function db_price($price){
    return bcmul($price,100);
}

function display_price($price){
    return number_format(bcdiv($price,100,2),2,".","");
}

/**
 * @param int $number
 * @return int
 */
function convert_negative_number(int $number){
    return $number * -1;
}

function convert_km($m){
    return round($m / \App\Consts\GlobalConst::KM_TO_M,2);
}
function array_append(array $array1,array $array2){
    foreach ($array2 as $value){
        $array1[] = $value;
    }
    return $array1;
}


function distance($lat1, $lng1, $lat2, $lng2, $unit = 'km')
{
    // 将角度转为狐度
    $radLat1 = deg2rad($lat1); //deg2rad()函数将角度转换为弧度
    $radLat2 = deg2rad($lat2);
    $radLng1 = deg2rad($lng1);
    $radLng2 = deg2rad($lng2);
    $a = $radLat1 - $radLat2;
    $b = $radLng1 - $radLng2;
    $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137;
    $s = ($unit == 'km' ? $s : $s * \App\Consts\GlobalConst::KM_TO_M);
    return $s;
}

function format_task_order(array $order, array $yunTu = [], $userType = UserConst::LABEL_TYPE_EMPLOYER,$userId = 0){
    $tmp['order_no'] = $order['order_no'];
    $tmp['user_id'] = $order['user_id'];
    $tmp['user_avatar'] = $order['user']['user_avatar'] ?? '';
    $tmp['user_name'] = $order['user']['user_name'] ?? '';
    $tmp['employer_level'] = $order['user']['employer_level'] ?? '';
    $tmp['order_name'] = $order['order_name'] ?? '';
    if($order['user_id'] == $userId && $order['services']){
        //雇主查看的是所有支付总价
        $serviceTotal =  array_sum(array_values($order['services']));
        $tmp['display_origin_price'] = display_price(bcadd($order['origin_price'],$serviceTotal));
        $tmp['display_pay_price'] = display_price(bcadd($order['pay_price'],$serviceTotal));
    }else {
        $tmp['display_origin_price'] = $order['display_origin_price'];
        $tmp['display_pay_price'] = $order['display_pay_price'];
    }
    $tmp['display_helper_price'] = display_price($order['origin_price']);
    $tmp['display_order_type'] = $order['display_order_type'];
    $tmp['display_cancel_type'] = $order['display_cancel_type'];
    $tmp['display_employer_order_state'] = $order['display_employer_order_state'];
    $tmp['order_type'] = $order['order_type'];
    $tmp['order_state'] = $order['order_state'];
    $tmp['address_list'] = $order['address_list'];
    $tmp['services'] = $order['services'];
    $tmp['display_services'] = $order['display_services'];
    $tmp['start_time'] = $order['start_time'];
    $tmp['end_time'] = $order['end_time'];
    $tmp['order_category_desc'] = $order['order_category_desc'];
    $tmp['order_category'] = $order['category'];
    $tmp['distance'] = $order['distance'];
    $tmp['distance_km'] = convert_km($order['distance']);
    $tmp['first_address_distance'] = $yunTu['_distance'] ?? 0;
    $tmp['first_address_location'] = $yunTu['_location'] ?? '';
    $tmp['first_address_yuntu_id'] = $yunTu['_id'] ?? 0;
    $tmp['service_time'] = date('m/d H:i',strtotime($tmp['start_time'])) .' ~ '.date('m/d H:i',strtotime($tmp['end_time']));
    $tmp['comment_id'] = $order['comment_id'];
    return $tmp;
}

function format_receiver_order(array &$receiver, array $user, array $taskOrder = []){
    $receiver['helper_level'] = $user['helper_level'] ?? 0;
    $receiver['user_name'] = $user['user_name'] ?? 0;
    $receiver['user_avatar'] = $user['user_avatar'] ?? 0;
    $receiver['display_quoted_price'] = display_price($receiver['quoted_price']);
    $receiver['display_receive_state'] = OrderConst::getHelperStateList($receiver['receive_state']);
    $receiver['display_order_type'] = OrderConst::getTypeList($receiver['order_type']);
    $receiver['bottom_receive_state'] =  OrderConst::getHelperStateList($receiver['receive_state']);
    if($receiver['order_type'] == OrderConst::TYPE_COMPETITION){
        if($receiver['user_id'] == getLoginUserId()) {
            $quoted_price = display_price($receiver['quoted_price']);
            $receiver['bottom_receive_state'] = "报价{$quoted_price}元," . OrderConst::getHelperStateList($receiver['receive_state']);
        }
    }
    if($taskOrder) {
        $validTime =  valid_between_time($taskOrder['start_time'],$taskOrder['end_time']);
        $receiver['time_valid'] = $validTime;
        if($receiver['receive_state'] == OrderConst::HELPER_STATE_RECEIVE && $validTime['status'] == 2 && $receiver['user_id'] == getLoginUserId()){
            $receiver['bottom_receive_state'] = format_time_by_minute($validTime['diff_minutes'],$validTime['status']);
        }
    }
    $receiver['is_self'] = ($receiver['user_id'] == getLoginUserId());

    $receiver['real_name'] = $user['real_name'] ?? '';
    $receiver['idcard'] = $user['idcard'] ?? '';

    return $receiver;
}

function format_state_display(\Illuminate\Database\Eloquent\Model $taskOrder ,\Illuminate\Database\Eloquent\Model $receive = null){
    $return = [];
    $return['order_state'] = $taskOrder['order_state'];
    $return['display_employer_order_state'] = OrderConst::getEmployerStateList($taskOrder['order_state']);
    $return['receive_state'] = $receive['receive_state'] ?? 0;
    $return['display_receive_state'] = OrderConst::getHelperStateList($receive['receive_state']);
    return $return;
}

function array_to_string(array  $array){
    $string = '';
    foreach ($array as $key=>$value){
        $string .= ($string ? ',' : '') .$key.'='.$value;
    }
    return $string;
}

function random_float($min = 0, $max = 1) {
    return $min + mt_rand() / mt_getrandmax() * ($max - $min);
}

function rand_db_id($maxId,$minId,$limit){
    $randDbId = round(random_float() * ($maxId-$minId) + $minId);
    if(($maxId - $randDbId) <= $limit){//最大用户ID与随机用户ID差什小于limit数，随机用户ID值减去limit数，保证取到真实的limit数
        if($randDbId > $limit) {
            $randDbId -= $limit;
        }else{
            $randDbId = $minId;
        }
    }
    return $randDbId;
}

/**
 * @param $sendUserId
 * @param int $userId
 * @param int $type
 * @param string $title
 * @param string $content
 * @param string $businessNo
 * @throws BusinessException
 */
function single_notice_send_message($sendUserId, int $userId,int $type, string $title, string $content,string  $businessNo){
    $params = [
        'business_no'=>$businessNo,
        'notice_type'=>MessageConst::TYPE_NOTICE,
        'notice_sub_type' => $type,
        'action' => 'toPage',
        'page' => '/pages/message/index',
        'query' => ['tab'=>0]
    ];
    $noticeService = new NoticeMessageService();
    $pushService = new PushService();
    $noticeService->sendNotice($sendUserId, $userId, $title,$content, $type, $params);
    $taskNo = strlen($businessNo) > 15 ? $businessNo : \App\Utils\UniqueNo::buildTaskOrderNo($userId,$type);
    $pushService->toSingle($type, $userId, $title, $content, $taskNo, $params);
}

function list_notice_send_message( array $userIds,int $type, string $title, string $content, array $params = []){

}

/**
 * @param int $userId
 * @param int $type
 * @param string $orderNo
 * @param float $priceChange
 * @return bool
 * @throws BusinessException
 */
function single_order_send_message( int $userId,int $type, string $orderNo = '',$priceChange = 0){
    $pushService = new PushService();
    $commentService = new OrderMessageService();
    $orderDetailService = new DetailTaskOrderService();
    $order = $orderDetailService->getOrder($orderNo,'',false,false);
    if(empty($order)){
        return false;
    }
    if($priceChange > 0){
        $priceChange = display_price($priceChange);
    }
    $params = [
        'business_no'=>$orderNo,
        'notice_type'=>MessageConst::TYPE_ORDER,
        'notice_sub_type' => $type,
        'action' => 'toPage',
        'page' => '/pages/task/detail',
        'query' => ['order_no'=>$orderNo]
    ];
    $employerUserId = $order['user_id'];
    $helperUserId = ($type == MessageConst::TYPE_ORDER_HELPER_NEW ? $userId : $order['helper_user_id']);
    $orderName = $order['order_name'];
    $message = MessageConst::getOrderNoticeMessage($type,$orderName,$employerUserId,$helperUserId,$priceChange);
    if(empty($message)){
        return false;
    }
    if($message['to_user_id'] > 0){
        $params['message_id'] = $commentService->sendSingleNotice(0, $message['to_user_id'], $message['title'], $message['content'], $type, $params);
        $pushService->toSingle($type, $message['to_user_id'], $message['title'], $message['content'], $orderNo, $params);
    }
    return true;
}

/**
 * @param array $userIds
 * @param int $type
 * @param string $orderNo
 * @return bool
 * @throws BusinessException
 */
function list_order_send_message( array $userIds,int $type, string $orderNo = ''){
    $pushService = new PushService();
    $commentService = new OrderMessageService();
    $orderDetailService = new DetailTaskOrderService();
    $order = $orderDetailService->getOrder($orderNo,'',false,false);
    if(empty($order)){
        return false;
    }
    $params = [
        'business_no'=>$orderNo,
        'notice_type'=>MessageConst::TYPE_ORDER,
        'notice_sub_type' => $type,
        'action' => 'toPage',
        'page' => '/pages/task/detail',
        'query' => ['order_no'=>$orderNo]
    ];
    $employerUserId = $order['user_id'];
    $orderName = $order['order_name'];
    $helperUserId = $order['helper_user_id'];
    $message = MessageConst::getOrderNoticeMessage($type,$orderName,$employerUserId,$helperUserId);
    if(empty($message)){
        return false;
    }
    $noticeId = $commentService->sendBatchNotice(0, $userIds, $message['title'], $message['content'], $type, $params);
    $params['message_id'] = $noticeId;
    $pushService->toList($type, $userIds, $message['title'], $message['content'], $orderNo, $params);
    return true;
}


function valid_between_time($starTime,$endTime,$compareTime = null){
    $starTime = Carbon::parse($starTime);
    $endTime = Carbon::parse($endTime);
    $compareTime = $compareTime ? Carbon::parse($compareTime) : Carbon::now();
    $return = [
        'range_diff' => $endTime->diffInMinutes($starTime)
    ];
    if($compareTime->isBefore($starTime)){
        $diffMinutes = $starTime->diffInMinutes($compareTime);
        $return['status'] = 0;
        $return['status_desc'] ='未开始';
        $return['diff_minutes'] = $diffMinutes;//多久后开始
        return $return;
    }
    if($compareTime->isBefore($endTime)){
        $diffMinutes = $endTime->diffInMinutes($compareTime);
        $return['status'] = 1;//表示还未结束/未过期
        $return['status_desc'] ='未过期';
        $return['diff_minutes'] = $diffMinutes;//多久后结束
        return $return;
    }
    if($endTime->isBefore($compareTime)){
        $diffMinutes = $compareTime->diffInMinutes($endTime);
        $return['status'] = 2;//
        $return['status_desc'] ='已过期';
        $return['diff_minutes'] = $diffMinutes;//结束了多久、过期了多久
        return $return;
    }
    return $return;
}

function format_time_by_minute($minute,$status = 2){
    $minuteUnit = 60;
    $desc = '';
    if($status == 0){
        if($minute <= 30){
            $desc = "任务开始还剩{$minute}分钟";
        }
    }elseif ($status == 1){
        if($minute <= 30){
            $desc = "任束结束还剩{$minute}分钟";
        }
    }elseif($status == 2) {
        $desc = '任务已逾期';
        if ($minute < $minuteUnit) {
            $desc .= $minute . '分钟';
        } else {
            $hour = bcdiv($minute, $minuteUnit);
            $diffMinute = $minute - ($minuteUnit * $hour);
            $desc .= $hour . '时' . $diffMinute . '分钟';
        }
    }
    return $desc;
}

/**
 * @param int $orderSate
 * @param int  $payPrice 任务价格，单位分
 * @param int $userType 取消用户类型
 * @param string $startTime
 * @param string $endTime
 * @return float|int
 */
function cancel_compensate_price(int $orderSate,int $payPrice,int $userType,string $startTime,string $endTime){
    if($userType == UserConst::TYPE_EMPLOYER && !in_array($orderSate,[OrderConst::EMPLOYER_STATE_DELIVERED,OrderConst::EMPLOYER_STATE_RECEIVE,OrderConst::EMPLOYER_STATE_REFUSE_DELIVERY])){
        return 0;
    }elseif($userType == UserConst::TYPE_HELPER && !in_array($orderSate,[OrderConst::HELPER_STATE_RECEIVE,OrderConst::HELPER_STATE_DELIVERED])){
        return 0;
    }
    $percentile = \App\Consts\GlobalConst::PERCENTILE;
    $scale = 3;
    $payPrice = display_price($payPrice);
    $now = Carbon::now();
    $response = valid_between_time($startTime,$endTime);
    $status = $response['status'];
    $diffMinutes = $response['diff_minutes'];
    $diffRange = $response['range_diff'];
    if($status == 0){//任务还未开始
        if($diffMinutes > 15){//任务开始15分钟之前赔偿不需赔偿
            return 0;
        }else{
            $compensatePrice =  round(bcmul($payPrice, 0.05,$scale),2 );
            return bcmul($compensatePrice,$percentile);
        }
    }
    //任务开始、逾期
    if($status == 2){
        $diffRange2 = $diffRange * 2;
        if($diffMinutes > $diffRange2 && $userType == UserConst::TYPE_EMPLOYER && !in_array($orderSate,[ OrderConst::EMPLOYER_STATE_DELIVERED,OrderConst::EMPLOYER_STATE_REFUSE_DELIVERY])){
            return 0;//延期时间大于任务总时长2倍，且帮手未交付，雇主取消则不赔偿
        }
    }
    $startMinutes = $now->diffInMinutes($startTime);//任务已开始多长时间
    $fist = round(bcmul($payPrice, 0.05,$scale),$scale );
    $payPrice = bcsub($payPrice,$fist,$scale);//第二部分钱去掉第一部分钱
    $minutePrice = bcdiv($payPrice,$diffRange,$scale);//每分钟的钱
    $second = bcmul($minutePrice , $startMinutes,$scale);//已开始后应该赔偿的钱
    $second = bcmul($second,0.3,$scale);//逾期时间可能很长，需要乘以百份比
    $compensatePrice = bcadd($fist,$second,$scale);
    $compensatePrice = $compensatePrice > $payPrice ? $payPrice : $compensatePrice;
    $compensatePrice = bcmul(round($compensatePrice,2),$percentile);
    return $compensatePrice;
}


/**
 * 逾期时长（距任务截止时间）÷ 【任务总时长*订单总金额*10%】
 *
 * @param int $payPrice 传入分
 * @param string $startTime
 * @param string $endTime
 * @return int|string
 */
function overtime_compensate_price(int $payPrice,string $startTime,string $endTime){
    $scale = 3;
    $percentile = \App\Consts\GlobalConst::PERCENTILE;
    $response = valid_between_time($startTime,$endTime);
    $status = $response['status'];
    $diffMinutes = $response['diff_minutes'];//逾期时长
    $diffRange = $response['range_diff'];//任务总时长
    if($status != 2){
        return 0;
    }
    $compensatePrice = display_price($payPrice);
    $compensatePrice = bcmul($diffRange,$compensatePrice,$scale);
    $compensatePrice = bcmul($compensatePrice,0.1,$scale);
    $compensatePrice = bcdiv($diffMinutes,$compensatePrice,2);
    $compensatePrice = db_price($compensatePrice);
    return $compensatePrice < $payPrice ? $compensatePrice : $payPrice;
}

function get_time_period($monthTotal = 6){
    $now = Carbon::now();
    $dataFormat = 'Y-m';
    $format = $now->format($dataFormat);
    $year = $now->format('Y');
    $return[$format] = '本月';
    for ($i=1;$i<$monthTotal;$i++){
        $month = convert_negative_number($i);
        $date = Carbon::now()->addMonths($month);
        $format = $date->format($dataFormat);
        $m = $date->format('m');
        $nextYear = $date->format('Y');
        if($year != $nextYear){
            $return[$format] = $date->format('Y/m');
        }else {
            $return[$format] = "{$m}月";
        }
    }
    return $return;
}

function getLoginUserId(){
    $login =  \request()->get('userLogin');
    return $login['user_id'] ?? 0;
}

function encryptPhone($phone){
    return substr_replace($phone, '****', 3, 4);
}

function calcAltitude(array $data){
    if($data['city'] == '省直辖县级行政区划'){
        $data['city'] = '';
    }
    if($data['city'] == '自治区直辖县级行政区划'){
        $data['city'] = '';
    }
    if($data['region'] == '市辖区'){
        $data['region'] = '';
    }
    if($data['city'] == '市辖区'){
        $data['city'] = '';
    }
    $street = $data['street'] ?? '';
    $detail = $data['address_detail'] ?? '';
    $data = $data['province'].$data['city'].$data['region'].$street.$detail;
    $AMap = new AMap();
    return $AMap->getAltitudeByAddress($data);
}


function getDeviceType()
{
    //全部变成小写字母
    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    $type = 'other';
    //分别进行判断
    if(strpos($agent, 'iphone') || strpos($agent, 'ipad'))
    {
        $type = 'ios';
    }

    if(strpos($agent, 'android'))
    {
        $type = 'android';
    }
    return $type;
}

/**
 * @return YunTuInterface
 */
function getYunTu(){
    $amap = config('map.enable','amap');
    if($amap == 'amap'){
        return new YunTu();
    }else if($amap == 'tencent'){
        return new TencentYunTu();
    }
}
