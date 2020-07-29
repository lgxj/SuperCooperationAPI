<?php


namespace App\Services\Trade\Fund;


use App\Bridges\User\UserBankCardBridge;
use App\Bridges\User\UserBridge;
use App\Consts\DBConnection;
use App\Consts\ErrorCode\WithdrawErrorCode;
use App\Consts\GlobalConst;
use App\Consts\Trade\PayConst;
use App\Consts\Trade\WithDrawConst;
use App\Consts\UserConst;
use App\Events\Funds\WithDrawEvent;
use App\Exceptions\BusinessException;
use App\Models\Trade\Fund\WithdrawApply;
use App\Services\Trade\Pay\Gateway\BalancePayment;
use App\Services\Trade\Pay\Gateway\ScWeixin;
use App\Services\User\BankCardService;
use App\Services\User\UserService;
use App\Utils\Dingding;
use App\Utils\UniqueNo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yansongda\LaravelPay\Facades\Pay;
use Yansongda\Pay\Exceptions\InvalidGatewayException;

/**
 * 提现服务
 *
 * Class WithDrawService
 * @package App\Services\Trade\Fund
 */
class WithDrawService
{

    /**
     * @param int $userId 提现用户
     * @param float $money 提现金额 单位元
     * @param int $type 提现类型
     * @param int|string $id 提现账户
     * @param int $withDrawWaterId 重试时，原来的提现ID
     * @param  string $transeferType
     * @return array
     * @throws BusinessException
     * @throws InvalidGatewayException
     * @throws \Exception
     */
    public function withDraw($userId, $money, $type, $id, $transeferType = 'app',$withDrawWaterId = 0){
        $money = db_price($money);
        $accountService = new AccountService();
        if($userId <= 0){
            throw  new BusinessException('用户信息错误',WithdrawErrorCode::CHECK_USER_ERROR);
        }
        if($money <= 0){
            throw  new BusinessException("提现金额不能小于零",WithdrawErrorCode::CHECK_PRICE_ERROR);
        }
        if(empty($id)){
            throw new BusinessException('提现渠道错误',WithdrawErrorCode::CHECK_CHANNEL_ERROR);
        }
        $user = $this->getUserBridge()->user($userId);
        if(empty($user)){
            throw new BusinessException('用户不存在',WithdrawErrorCode::CHECK_USER_ERROR);
        }
        if(!$user['user_status']){
            throw new BusinessException('您账户已被锁定，请联平台客服',WithdrawErrorCode::CHECK_USER_LOCKED);
        }
        if($money < WithDrawConst::MIN_WITH_DRAW){
            $minMax = display_price(WithDrawConst::MIN_WITH_DRAW);
            throw new BusinessException("最低提现金额{$minMax}元",WithdrawErrorCode::CHECK_MIN_PRICE);
        }
        $drawData = [];
        if($withDrawWaterId > 0){
            $withDrawApplyModel = $this->getWithDrawApplyModel();
            $drawData = $withDrawApplyModel->where(['user_id'=>$userId,'withdraw_id'=>$withDrawWaterId])->first()->toArray();
            if(in_array($drawData['withdraw_type'],[WithDrawConst::STATUS_COMPLETE,WithDrawConst::STATUS_FAILED])){
                throw new BusinessException('该笔提现流程已完成，请不要重试',WithdrawErrorCode::CHECK_STATE_COMPLETE);
            }
        }
        $account = $accountService->getAccountByUserId($userId);
        $thirdAccount = $this->getWithDrawAccount($userId,$type,$id);
        if(empty($thirdAccount)){
            throw new BusinessException('提现账户错误',WithdrawErrorCode::CHECK_ACCOUNT_ERROR);
        }

        if($money > $account['available_balance']){
            throw new BusinessException('余额不足，请重新填写提现金额',WithdrawErrorCode::CHECK_BALANCE_NOT_ENOUGH);
        }
        $status = $money > WithDrawConst::WITH_DRAW_CHECK ? WithDrawConst::STATUS_UN_VERIFY : WithDrawConst::STATUS_VERIFY;
        if($withDrawWaterId <= 0){
            $drawData = [
                'user_id' => $userId,
                'withdraw_no' => UniqueNo::buildWithdrawNo($userId,$type),
                'withdraw_money' => $money,
                'withdraw_type' => $type,
                'withdraw_account' => $id,
                'transfer_type' => ($type == WithDrawConst::TYPE_ALIPAY) ? WithDrawConst::TRANSFER_TYPE_ALIPAY : WithDrawConst::TRANSFER_TYPE_WEIXIN,
                'status' => $status,
                'withdraw_id' => 0
            ];

            if($drawData['status'] == WithDrawConst::STATUS_UN_VERIFY){
                //大额订单提现，等待审核
                $this->saveWithDraw($drawData);
                return $drawData;
            }
        }
        $connection = DBConnection::getTradeConnection();
        try {
            $balancePayment = new BalancePayment();
            if($drawData['withdraw_type'] == WithDrawConst::TYPE_WEIXIN){
                $data = [
                    'openid' => $thirdAccount['grant_login_identify'],
                    'partner_trade_no' => $drawData['withdraw_no'],
                    'check_name' => 'NO_CHECK',
                    'amount' => $drawData['withdraw_money'],
                    'desc' => $user['user_name'].'-提现',
                    'type' => $transeferType,
                ];
                $weixin = Pay::wechat();;
                $response = $weixin->transfer($data);
                $errCode = $response['err_code'] ?? '';
                $returnCode = $response['return_code'] ?? '';
                $resultCode =  $response['result_code'] ?? '';
                $errCode = strtoupper($errCode);
                $unRetryCode = $this->getWeixinUnRetryCode();
                if($returnCode == 'FAIL'){
                    throw new BusinessException($response['return_msg']);
                }
                $drawData['channel_trade_no'] = $response['payment_no'];
                $drawData['channel_pay_time'] = $response['payment_time'];
                $drawData['channel_error_code'] = $errCode;
                if($resultCode == 'FAIL' ){
                    if(in_array($errCode,$unRetryCode)) {
                        throw new BusinessException($response['err_code_des'],WithdrawErrorCode::WITHDRAW_FAILED);
                    }else{
                        $drawData['status'] = WithDrawConst::STATUS_RETRY;
                    }
                }else{
                    $drawData['status'] = WithDrawConst::STATUS_COMPLETE;
                }

            }elseif($drawData['withdraw_type'] == WithDrawConst::TYPE_ALIPAY){
                $alipay = Pay::alipay();
                $data = [
                    'out_biz_no' => $drawData['withdraw_no'],
                    'check_name' => 'NO_CHECK',
                    'trans_amount' => display_price($drawData['withdraw_money']),
                    'product_code' => 'TRANS_ACCOUNT_NO_PWD',
                    'order_title' => '提现',
                    'remark' => $user['user_name'].'-提现',
                    'biz_scene' => 'DIRECT_TRANSFER',
                    'payee_info' => [
                            'identity'=>$thirdAccount['grant_login_identify'],
                            'identity_type' => 'ALIPAY_USER_ID'
                        ]
                ];
                $response = $alipay->transfer($data);
                $errCode = $response['error_code'] ?? '';
                $status =  $response['status'] ?? '';//SUCCESS,REFUND,DEALING,SUCCESS
                $errCode = strtoupper($errCode);
                if($status == 'FAIL'){
                    $reason = $response['fail_reason'] ?? '';
                    Dingding::robot(new BusinessException($reason));
                    throw new BusinessException('系统繁忙，请稍后',WithdrawErrorCode::WITHDRAW_FAILED);
                }
                if($status == 'REFUND'){
                    $drawData['status'] = WithDrawConst::STATUS_FAILED;
                }elseif($status == 'DEALING'){
                    $drawData['status'] = WithDrawConst::STATUS_RETRY;
                }
                $drawData['channel_trade_no'] = $response['order_id'];
                $drawData['channel_pay_time'] = $response['trans_date'];
                $drawData['status'] = WithDrawConst::STATUS_COMPLETE;
                $drawData['channel_error_code'] = $errCode;

            }elseif($drawData['withdraw_type'] == WithDrawConst::TYPE_BANK){
                $scWeixin = new ScWeixin(config('pay.wechat'));
                $data = [
                    'enc_bank_no' => $thirdAccount['card_no'],
                    'enc_true_name' => $thirdAccount['real_name'],
                    'bank_code' => $this->getBankNo($thirdAccount['bank_name']),
                    'partner_trade_no' => $drawData['withdraw_no'],
                    'amount' => $drawData['withdraw_money'],
                    'desc' => '提现',
                ];
                $response = $scWeixin->transferBank($data);
                $errCode = $response['err_code'] ?? '';
                $returnCode = $response['return_code'] ?? '';
                $resultCode =  $response['result_code'] ?? '';
                $errCode = strtoupper($errCode);
                $unRetryCode = $this->getWeixinBankUnRetryCode();
                if($returnCode == 'FAIL'){
                    Dingding::robot(new BusinessException($response['return_msg']));
                    throw new BusinessException('系统繁忙，稍后重试！',WithdrawErrorCode::WITHDRAW_FAILED);
                }
                $drawData['channel_trade_no'] = $response['payment_no'];
                $drawData['channel_error_code'] = $errCode;
                $drawData['withdraw_fee_money'] = $response['cmms_amt'];
                if($resultCode == 'FAIL' ){
                    if(in_array($errCode,$unRetryCode)) {
                        throw new BusinessException($response['err_code_des']);
                    }else{
                        $drawData['status'] = WithDrawConst::STATUS_RETRY;
                    }
                }else{
                    $drawData['channel_pay_time'] = Carbon::now();
                    $drawData['status'] = WithDrawConst::STATUS_COMPLETE;
                }
            }

            $connection->beginTransaction();
            if ($drawData['status'] == WithDrawConst::STATUS_COMPLETE && $withDrawWaterId <= 0) {
                //第一次提现就是完成
                $balancePayment->pay($userId, $drawData['withdraw_money']);
                $money = convert_negative_number($drawData['withdraw_money']);
                $this->getInoutLogService()->addInoutLog($userId,$money,PayConst::CHANNEL_BALANCE,PayConst::INOUT_WITHDRAW,PayConst::SOURCE_WITHDRAW,$drawData['withdraw_no'],$userId);
            }elseif($drawData['status'] == WithDrawConst::STATUS_COMPLETE && $withDrawWaterId > 0){
                //第一次失败，重试成功，解冻
                $balancePayment->unFreezeWithDraw($userId, $drawData['withdraw_no'],false);
                $money = convert_negative_number($drawData['withdraw_money']);
                $this->getInoutLogService()->addInoutLog($userId,$money,PayConst::CHANNEL_BALANCE,PayConst::INOUT_WITHDRAW,PayConst::SOURCE_WITHDRAW,$drawData['withdraw_no'],$userId);

            } elseif ($drawData['status'] == WithDrawConst::STATUS_RETRY  && $withDrawWaterId <= 0) {
                //第一次失败，冻结，等待重试,重试成功解冻，重试失败这里不处理
                $balancePayment->freeze($userId, $drawData['withdraw_money'], WithDrawConst::FREEZE_TYPE_WITHDRAW, $drawData['withdraw_no']);
            }
            $withDraw = $this->saveWithDraw($drawData);
            $connection->commit();;
            event(new WithDrawEvent($userId,$drawData['status'],$withDraw['withdraw_id']));
            return  $drawData;
        }catch (\Exception $e){
            $connection->rollBack();
            Log::error("提现失败 withdrawNo:{$drawData['withdraw_no']} message:{$e->getMessage()}");
            Dingding::robot(new BusinessException($e->getMessage()));
            throw new BusinessException('系统繁忙，请稍后重试哦！',WithdrawErrorCode::WITHDRAW_FAILED);
        }

    }

    /**
     * @param $userId
     * @param $withDrawNo
     * @param int $agreeStatus
     * @return array
     * @throws BusinessException
     * @throws InvalidGatewayException
     */
    public function verify($userId,$withDrawNo,$agreeStatus = WithDrawConst::STATUS_VERIFY,$transerType = 'app'){
        $withDraw = $this->getWithDrawApplyModel()->where(['withdraw_no'=>$withDrawNo,'user_id'=>$userId])->first();
        if(empty($withDraw)){
            throw new BusinessException('提现记录不存在');
        }
        if($agreeStatus == WithDrawConst::STATUS_VERIFY){
            $this->withDraw($userId,display_price($withDraw['withdraw_money']),$withDraw['type'],$withDraw['withdraw_account'],$transerType,$withDraw['withdraw_id']);
            $withDraw->status = $agreeStatus;
            $withDraw->save();
        }else{
            $withDraw->status = $agreeStatus;
            $withDraw->save();
        }
        return $withDraw->toArray();
    }

    /**
     * @param $userId
     * @param $withDrawNo
     * @return array
     * @throws BusinessException
     * @throws InvalidGatewayException
     */
    public function retry($userId,$withDrawNo,$transerType = 'app'){
        $withDraw = $this->getWithDrawApplyModel()->where(['withdraw_no'=>$withDrawNo,'user_id'=>$userId])->first();
        if(empty($withDraw)){
            throw new BusinessException('提现记录不存在');
        }
        if($withDraw['retry_times'] > 3){
            //重试超过一定次数处理
        }
        $this->withDraw($userId,display_price($withDraw['withdraw_money']),$withDraw['type'],$withDraw['withdraw_account'],$transerType,$withDraw['withdraw_id']);
        $withDraw->increment('retry_times',1);
        return $withDraw->toArray();
    }

    public function list($userId,$timePeriod,$page=1,$pageSize = GlobalConst::PAGE_SIZE,$lastId = null){
        $carbonTime = Carbon::parse($timePeriod);
        $start = Carbon::parse($timePeriod)->firstOfMonth();
        $end = $carbonTime->endOfMonth();
        $model = WithdrawApply::where(['user_id' => $userId])->whereBetween('created_at', [$start, $end])->select('withdraw_id')->orderByDesc('withdraw_id');
        if(!is_null($lastId)){
            $model->forPageBeforeId($pageSize,$lastId,'withdraw_id');
        }else {
            $model->forPage($page,$pageSize);
        }
        $withDrawIds = $model->pluck('withdraw_id');//延迟查讯，解决慢查、file sort
        if(empty($withDrawIds)){
            return [];
        }
        $total = WithdrawApply::where(['user_id' => $userId])->whereBetween('created_at', [$start, $end])->sum('withdraw_money');
        $logs = WithdrawApply::whereIn('withdraw_id',$withDrawIds)->get()->keyBy('withdraw_id');
        $userBanks = $this->getBankBridge()->findAllByUid($userId);
        $userBanks = collect($userBanks)->keyBy('bank_id');
        $list = [];
        $logs = $logs->toArray();
        foreach ($withDrawIds as $withDrawId) {
            $log = $logs[$withDrawId] ?? [];
            if (empty($log)) {
                continue;
            }
            $tmp['withdraw_id'] = $log['withdraw_id'];
            $tmp['user_id'] = $log['user_id'];
            $tmp['withdraw_money'] = $log['withdraw_money'];
            $tmp['display_withdraw_money'] = display_price($log['withdraw_money']);
            $tmp['fee_money'] = $log['withdraw_fee_money'];
            $tmp['display_fee_money'] = display_price($log['withdraw_fee_money']);
            $tmp['withdraw_type'] = $log['withdraw_type'];
            $tmp['display_withdraw_type'] = WithDrawConst::getTypeList($log['withdraw_type']);
            $tmp['display_transfer_type'] = WithDrawConst::getTransferTypeList($log['transfer_type']);
            $tmp['display_status'] = WithDrawConst::getStatusList($log['status']);
            $tmp['withdraw_account'] = $log['withdraw_account'];
            $tmp['status'] = $log['status'];
            $tmp['created_at'] = $log['created_at'];
            $tmp['withdraw_desc'] = '';
            if($log['withdraw_type'] == WithDrawConst::TYPE_BANK && isset($userBanks[$log['withdraw_account']])){
                $bank = $userBanks[$log['withdraw_account']];
                $bankName = explode('-',$bank['bank_name']);
                $cardNo = substr($bank['card_no'],-4);
                $tmp['withdraw_desc'] = "余额-转出到{$bankName[0]}({$cardNo})";
            }elseif($log['withdraw_type'] == WithDrawConst::TYPE_ALIPAY){
                $tmp['withdraw_desc'] = "余额-转出到支付宝";
            }elseif($log['withdraw_type'] == WithDrawConst::TYPE_WEIXIN){
                $tmp['withdraw_desc'] = "余额-转出到微信";
            }
            $list[] = $tmp;
        }
        if($total > 0 ){
            $total = display_price($total);
        }
        return ['list'=>$list,'total'=>$total];
    }


    /**
     * @param array $withDraw
     * @return mixed
     * @throws BusinessException
     */
    public function saveWithDraw(array $withDraw){
        $validate = Validator::make($withDraw,[
            'user_id'=>'required|integer',
            'withdraw_no'=>'required|integer',
            'withdraw_type'=>'required|integer',
            'withdraw_account'=>'required',
            'withdraw_money' => 'required|integer'
        ],[
            'user_id.required' => '用户信息不存在',
            'withdraw_no.required' => '提现流水号不能为空',
            'withdraw_type.required'=>"提现类型不能为空",
            'withdraw_account.required'=>"提现账户不能为空",
            'withdraw_money.required'=>"提现金额不能为空"
        ]);
        if($validate->fails()){
            throw new BusinessException($validate->errors()->first(),WithdrawErrorCode::CHECK_VALIDATION_ERROR);
        }
        if($withDraw['withdraw_id'] > 0){
            $withDrawModel = $this->getWithDrawApplyModel()->where(['user_id'=>$withDraw['user_id'],['withdraw_id'=>$withDraw['withdraw_id']]])->first();
        }else{
            $withDrawModel = $this->getWithDrawApplyModel();
        }
        $fields = $withDrawModel->getTableColumns();
        foreach ($fields as $field) {
            if ($field == $withDrawModel->getKeyName()) {
                continue;
            }
            if (isset($withDraw[$field])) {
                $withDrawModel->$field = $withDraw[$field];
            }
        }
        $withDrawModel->save();
        return $withDrawModel->toArray();
    }

    public function grantList($userId){
        $userBridge = $this->getUserBridge();
        $grantWeixin = $userBridge->findByUserGrantType($userId,UserConst::GRANT_LOGIN_TYPE_WEIXIN);
        $grantAlipay =  $userBridge->findByUserGrantType($userId,UserConst::GRANT_LOGIN_TYPE_ALIPAY);
        $list = [
            'alipay' => [
                'desc' => '支付宝',
                'id' => 0,
                'nickname' => '',
                'third_account_id'=>''
            ],
            'weixin' => [
                'desc' => '微信',
                'id' => 0,
                'nickname' => '',
                'third_account_id'=>''
            ]
        ];
        if($grantWeixin){
            $list['weixin']['id'] = $grantWeixin['grant_login_id'];
            $list['weixin']['third_account_id'] = $grantWeixin['grant_login_identify'];
            $list['weixin']['nickname'] = $grantWeixin['grant_user_nickname'];
        }
        if($grantAlipay){
            $list['alipay']['id'] = $grantAlipay['grant_login_id'];
            $list['alipay']['third_account_id'] = $grantAlipay['grant_login_identify'];
            $list['alipay']['nickname'] = $grantAlipay['grant_user_nickname'];
        }
        return $list;
    }

    public function getById($userId,$id){
        if($userId <= 0 || $id <= 0){
            return [];
        }
        $apply = WithdrawApply::where(['user_id'=>$userId,'withdraw_id'=>$id])->first();
        if(empty($apply)){
            return [];
        }
        $apply = $apply->toArray();
        $apply['withdraw_desc'] = '';
        if($apply['withdraw_type'] == WithDrawConst::TYPE_BANK){
           // $bank = $userBanks[$log['withdraw_account']];
            $apply['withdraw_desc'] = "余额-转出到银行";
        }elseif($apply['withdraw_type'] == WithDrawConst::TYPE_ALIPAY){
            $apply['withdraw_desc'] = "余额-转出到支付宝";
        }elseif($apply['withdraw_type'] == WithDrawConst::TYPE_WEIXIN){
            $apply['withdraw_desc'] = "余额-转出到微信";
        }
        return $apply;
    }
    protected function getWithDrawAccount($userId,$type,$id){
        $thirdAccount = [];
        if($type == WithDrawConst::TYPE_WEIXIN){
            $thirdAccount = $this->getUserBridge()->findByUserGrantId($userId,$id);
        }elseif($type == WithDrawConst::TYPE_ALIPAY){
            $thirdAccount = $this->getUserBridge()->findByUserGrantId($userId,$id);
        }elseif($type == WithDrawConst::TYPE_BANK){
            $thirdAccount = $this->getBankBridge()->find($userId,$id);
        }
        return $thirdAccount;
    }

    protected function getWithDrawApplyModel(){
        return new WithdrawApply();
    }
    /**
     * @return  BankCardService
     */
    protected function getBankBridge(){
        return new UserBankCardBridge(new BankCardService());
    }

    /**
     * @return  UserService
     */
    protected function getUserBridge(){
        return new UserBridge(new UserService());
    }

    protected function getInoutLogService(){
        return new InoutLogService();
    }
    protected function getBankNo($bankName){
        $space = [' '=>'','-'=>'','('=>'',')'=>''];
        foreach ($this->weixinBankNoList() as $name=>$no){
            $name = strtr($name,$space);
            if(mb_strpos($bankName,$name) != false){
                return $no;
            }
        }
        throw new BusinessException("微信企业付款不支持{$bankName}银行",WithdrawErrorCode::CHECK_BANK_NOT_SUPPORT);
    }

    protected function weixinBankNoList(){
        $list = [
            '工商银行'=>1002,
            '农业银行'=>1005,
            '建设银行'=>1003,
            '中国银行'=>1026,
            '交通银行'=>1020,
            '招商银行'=>1001,
            '邮储银行'=>1066,
            '民生银行'=>1006,
            '平安银行'=>1010,
            '中信银行'=>1021,
            '浦发银行'=>1004,
            '兴业银行'=>1009,
            '光大银行'=>1022,
            '广发银行'=>1027,
            '华夏银行'=>1025,
            '宁波银行'=>1056,
            '北京银行'=>4836,
            '上海银行'=>1024,
            '南京银行'=>1054,
            '长子县融汇村镇银行'=>4755,
            '长沙银行'=>4216,
            '浙江泰隆商业银行'=>4051,
            '中原银行'=>4753,
            '企业银行（中国）'=>4761,
            '顺德农商银行'=>4036,
            '衡水银行'=>4752,
            '长治银行'=>4756,
            '大同银行'=>4757,
            '河南省农村信用社'=>4115,
            '宁夏黄河农村商业银行'=>4150,
            '山西省农村信用社'=>4156,
            '安徽省农村信用社'=>4166,
            '甘肃省农村信用社'=>4157,
            '天津农村商业银行'=>4153,
            '广西壮族自治区农村信用社'=>4113,
            '陕西省农村信用社'=>4108,
            '深圳农村商业银行'=>4076,
            '宁波鄞州农村商业银行'=>4052,
            '浙江省农村信用社联合社'=>4764,
            '江苏省农村信用社联合社'=>4217,
            '江苏紫金农村商业银行股份有限公司'=>4072,
            '北京中关村银行股份有限公司'=>4769,
            '星展银行（中国）有限公司'=>4778,
            '枣庄银行股份有限公司'=>4766,
            '海口联合农村商业银行股份有限公司'=>4758,
            '南洋商业银行（中国）有限公司'=>4763
        ];
        return $list;
    }

    protected function getWeixinUnRetryCode(){
        return [
          'NO_AUTH',
          'AMOUNT_LIMIT',
          'PARAM_ERROR',
          'OPENID_ERROR',
          'SEND_FAILED',
          'XML_ERROR',
          'FATAL_ERROR',
          'RECV_ACCOUNT_NOT_ALLOWED',
          'PAY_CHANNEL_NOT_ALLOWED'
        ];
    }

    protected function getWeixinBankUnRetryCode(){
        return [
            'NO_AUTH',
            'AMOUNT_LIMIT',
            'PARAM_ERROR',
            'SIGNERROR',
            'FATAL_ERROR',
            'RECV_ACCOUNT_NOT_ALLOWED',
            'PAY_CHANNEL_NOT_ALLOWED',
            'SENDNUM_LIMIT',
            'SUCCESS',
            'NOTENOUGH',
            'ORDERPAID'
        ];
    }
}
