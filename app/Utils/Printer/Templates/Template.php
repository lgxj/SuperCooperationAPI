<?php

namespace App\Utils\Printer\Templates;



use App\Utils\Printer\Bo\TplDataSource;
use App\Utils\Printer\Devices\Command\ICommand;


/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 17/10/17
 * Time: 下午7:58
 */
abstract class Template
{
    private $template = '';

    /** @var ICommand */
    private $command = null;

    protected $kdtId = 0;

    protected $orderNo = '';

    protected $extra = [];

    /**
     * @var TplDataSource
     */
    protected $dataSource = null;

    public function __construct(ICommand $command, $kdtId, $orderNo, array $extra = [])
    {
        $this->command = $command;
        $this->kdtId = $kdtId;
        $this->orderNo = $orderNo;
        $this->extra = [];
        $deviceConfig = $command->getDevice()->getConfig();
        $this->dataSource = new TplDataSource($kdtId, $orderNo, $extra,$deviceConfig);
    }

    abstract protected function readData();

    abstract protected function template();

    /**
     * @return ICommand
     */
    protected function getCommand()
    {
        return $this->command;
    }

    public function render()
    {
        $this->readData();
        $this->template = $this->template();
        return $this->template;
    }

}
