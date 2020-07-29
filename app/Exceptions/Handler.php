<?php

namespace App\Exceptions;

use App\Consts\ErrorCode\MessageErrorCode;
use App\Consts\ErrorCode\PayErrorCode;
use App\Consts\ErrorCode\ReceiveErrorCode;
use App\Consts\ErrorCode\RequestErrorCode;
use App\Consts\ErrorCode\SmsErrorCode;
use App\Consts\ErrorCode\TaskOrderErrorCode;
use App\Consts\ErrorCode\UserErrorCode;
use App\Consts\ErrorCode\WithdrawErrorCode;
use App\Consts\GlobalConst;
use App\Utils\Dingding;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param \Exception $exception
     * @return void
     * @throws Exception
     */
    public function report(Exception $exception)
    {
        if (!$this->shouldntReport($exception)) {
            $codeBlankList = [
                SmsErrorCode::SMS_CODE_SEND_FAILED,
                UserErrorCode::CERTIFICATION_OKR_VERIFY_FAILED,
                TaskOrderErrorCode::SAVE_FAILED,
                TaskOrderErrorCode::COMPLETE_FAILED,
                TaskOrderErrorCode::CONFIRM_TASK_FAILED,
                TaskOrderErrorCode::CANCEL_FAILED,
                ReceiveErrorCode::RECEIVE_FAILED,
                ReceiveErrorCode::DELIVERY_FAILED,
                ReceiveErrorCode::CANCEL_FAILED,
                PayErrorCode::PAY_FAILED,
                PayErrorCode::NOTIFY_FAILED,
                PayErrorCode::NOTIFY_WEIXIN_FAILED,
                PayErrorCode::NOTIFY_ALIPAY_FAILED,
                PayErrorCode::NOTIFY_WEIXIN_REFUND_FAILED,
                WithdrawErrorCode::WITHDRAW_FAILED,
                MessageErrorCode::IM_REQUEST_FAILED,
                RequestErrorCode::ALIYUN_OSS_FAILED,
                RequestErrorCode::YUN_TU_FAILED,
                RequestErrorCode::YUN_TU_INFO,
                RequestErrorCode::GAO_DE_FAILED,
                MessageErrorCode::PUSH_FAILED
            ];
            if($exception instanceof BusinessException && in_array($exception->getCode(),$codeBlankList)){
                Dingding::robot($exception);
                return ;
            }
        }
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     * @param \Exception $exception
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws Exception
     */
    public function render($request, Exception $exception)
    {
        // 自定义异常返回
        if ($exception instanceof BusinessException) {
            return out($exception->getCode(), $exception->getMessage(), false, []);
        }
        // ValidationException
        if ($exception instanceof ValidationException) {
            return out(GlobalConst::FAIL, validate_errors($exception->validator->errors()));
        }
        if($request->ajax()) {
            return out($exception->getCode(), $exception->getMessage());
        }else {
            return parent::render($request, $exception);
        }
    }
}
