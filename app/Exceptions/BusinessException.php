<?php


namespace App\Exceptions;

use Exception;
use Throwable;
use App\Consts\GlobalConst;

class BusinessException extends Exception
{

    /**
     * ResException constructor.
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(
        $message = GlobalConst::FAIL_MSG,
        $code = GlobalConst::FAIL,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
