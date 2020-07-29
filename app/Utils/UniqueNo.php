<?php


namespace App\Utils;

use App\Consts\MessageConst;
use App\Consts\RealNameAuthConst;
use App\Consts\Trade\FeeConst;
use App\Consts\Trade\OrderConst;
use App\Consts\Trade\WithDrawConst;
use App\Exceptions\BusinessException;

/**
 * 唯一号码生成器
 *
 * 生码号码上带的有数据库表的分片，这条数据库落在那个水平分库分表吧
 *
 * Class UniqueNo
 * @package App\Utils
 */
class UniqueNo
{

    const BUSINESS_TYPE_TASK_ORDER = 1;
    const BUSINESS_TYPE_PAY = 2;
    const BUSINESS_TYPE_WITHDRAW =3;
    const BUSINESS_TYPE_INOUT = 4;
    const BUSINESS_TYPE_REFUND = 5;
    const BUSINESS_TYPE_FEE = 6;
    const BUSINESS_TYPE_FREEZE = 7;
    const BUSINESS_TYPE_PRICE_CHANGE = 8;
    const BUSINESS_TYPE_REAL_NAME_AUTH = 9; // 实名认证
    const BUSINESS_TYPE_FEE_REFUND = 10;    // 退款
    const BUSINESS_TYPE_PUSH_TASK = 11;     // 推送任务
    const BUSINESS_TYPE_TEL_CONTACT = 12;     // 电话联系
    /**
     * 分库分表的个数,999个分库分表
     */
    const SPLIT_DB_TABLE_NUM = 999;

    /**
     * 业务流水号生成器
     *
     * @param int $businessType
     * @param int $businessId
     * @param int $businessSubType
     * @return string
     * @throws BusinessException
     */
    public static function build(int $businessType,int $businessSubType,int $businessId) : string
    {
        //业务类型只能是2位数
        if($businessType <= 0 || $businessType > 99 || strlen($businessType) > 2){
            throw new BusinessException("业务类型异常");
        }
        if($businessId <= 0){
            throw new BusinessException("业务规则ID异常");
        }
        if($businessSubType < 0){
            $businessSubType = 0;
        }
        //子业务类型只能是2位数
        if($businessSubType > 99 ||  strlen($businessSubType) > 2){
            throw new BusinessException("子业务规则ID异常");
        }
        $date = date('ymd');
        $splitId = $businessId % self::SPLIT_DB_TABLE_NUM;
        $padBusinessType = str_pad($businessType,2,0,STR_PAD_LEFT);
        $padSubBusinessType = str_pad($businessSubType,2,0,STR_PAD_LEFT);
        $padSplitId = str_pad($splitId,3,0,STR_PAD_LEFT);
        $mt1 = mt_rand(100,500);
        $mt2 = mt_rand(500,999);
        $mt3 = mt_rand(100,499);
        $mt1 = $mt1+$mt3;
        return $date.$padBusinessType.$padSubBusinessType.$padSplitId.$mt1.$mt2;
    }

    /**
     * 任务单流水号生成器
     *
     * @param int $userId
     * @param int $businessSubType
     * @return string
     * @throws BusinessException
     */
    public static function buildTaskOrderNo(int $userId,int $businessSubType = OrderConst::TYPE_GENERAL) : string
    {
        return self::build(self::BUSINESS_TYPE_TASK_ORDER,$businessSubType,$userId);
    }

    /**
     * 支付单流水号生成器
     *
     * @param int $userId
     * @param int $businessType
     * @return string
     * @throws BusinessException
     */
    public static function buildPayNo(int $userId,int $businessType = OrderConst::PRICE_CHANGE_ORDER_PAY) : string
    {
        return self::build(self::BUSINESS_TYPE_PAY,$businessType,$userId);
    }
    /**
     * 提现流水号生成器
     *
     * @param int $userId
     * @param int $businessSubType
     * @return string
     * @throws BusinessException
     */
    public static function buildWithdrawNo(int $userId,int $businessSubType = 0) : string
    {
        return self::build(self::BUSINESS_TYPE_WITHDRAW,$businessSubType,$userId);
    }

    /**
     * 收支明细流水号生成器
     *
     * @param int $userId
     * @param int $businessSubType
     * @return string
     * @throws BusinessException
     */
    public static function buildInoutNo(int $userId,int $businessSubType = 0) : string
    {
        return self::build(self::BUSINESS_TYPE_INOUT,$businessSubType,$userId);
    }

    /**
 * 退款流水号生成器
 *
 * @param int $userId
 * @param int $businessSubType
 * @return string
 * @throws BusinessException
 */
    public static function buildRefundNo(int $userId,$businessSubType = 0) : string
    {
        return self::build(self::BUSINESS_TYPE_REFUND,$businessSubType,$userId);
    }
    /**
     * 退款流水号生成器
     *
     * @param int $userId
     * @param int $businessSubType
     * @return string
     * @throws BusinessException
     */
    public static function buildFeeRefundNo(int $userId,$businessSubType = 0) : string
    {
        return self::build(self::BUSINESS_TYPE_FEE_REFUND,$businessSubType,$userId);
    }
    /**
     * 平台收费流水号生成器
     *
     * @param int $userId
     * @param int $businessSubType
     * @return string
     * @throws BusinessException
     */
    public static function buildPlatformFeeNo(int $userId,int $businessSubType = FeeConst::TYPE_TRADE) : string
    {
        return self::build(self::BUSINESS_TYPE_FEE,$businessSubType,$userId);
    }


    /**
     * 冻结流水号生成器
     *
     * @param int $userId
     * @param int $businessSubType
     * @return string
     * @throws BusinessException
     */
    public static function buildFreezeNo(int $userId,int $businessSubType = WithDrawConst::FREEZE_TYPE_WITHDRAW) : string
    {
        return self::build(self::BUSINESS_TYPE_FREEZE,$businessSubType,$userId);
    }

    /**
     * 冻结流水号生成器
     *
     * @param int $userId
     * @param int $businessSubType
     * @return string
     * @throws BusinessException
     */
    public static function buildPriceWaterNo(int $userId,int $businessSubType = OrderConst::PRICE_CHANGE_ORDER_PAY) : string
    {
        return self::build(self::BUSINESS_TYPE_PRICE_CHANGE,$businessSubType,$userId);
    }

    /**
     * 实名认证流水号生成器
     *
     * @param int $userId
     * @param int $businessSubType
     * @return string
     * @throws BusinessException
     */
    public static function buildRealNameAuthNo(int $userId, $businessSubType = RealNameAuthConst::TYPE_OCR) : string
    {
        return self::build(self::BUSINESS_TYPE_REAL_NAME_AUTH, $businessSubType, $userId);
    }

    /**
     * 推送任务流水号
     * @param $businessSubType
     * @return string
     * @throws BusinessException
     */
    public static function buildPushTaskNo($businessSubType = MessageConst::TYPE_SEND_DEFAULT)
    {
        return self::build(self::BUSINESS_TYPE_PUSH_TASK, $businessSubType, 999999999);
    }

    /**
     * 推送任务流水号
     * @param $businessSubType
     * @return string
     * @throws BusinessException
     */
    public static function buildTelContactTaskNo(int $userId,$businessSubType)
    {
        return self::build(self::BUSINESS_TYPE_TEL_CONTACT, $businessSubType, $userId);
    }

    public static function getInfoByNo($no){
        $info['business_type']  =  intval(substr($no,6,2));
        $info['business_sub_type']  =  intval(substr($no,8,2));
        $info['split_table']  =  substr($no,10,3);
        return $info;
    }
}
