<?php
namespace App\Listeners;

use App\Bridges\Message\IMBridge;
use App\Events\User\LoginEvent;
use App\Events\User\LogoutEvent;
use App\Events\User\RegisterEvent;
use App\Events\User\UpdatedEvent;
use App\Services\Message\IMService;


class UserEventSubscriber extends ScEventListener
{
    /**
     * @var IMService
     */
    protected $IMBridge;

    public function __construct(IMBridge $IMBridge)
    {
        parent::__construct();
        $this->IMBridge = $IMBridge;
    }

    /**
     * 处理用户注册事件
     * @param RegisterEvent $event
     * @throws \App\Exceptions\BusinessException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function handleUserRegister(RegisterEvent $event)
    {
        $user = $event->user;
        $this->IMBridge->bindUser($user->user_id, $user->user_name, $user->user_avatar);
        $this->getTaskDayBridge()->increment('user_register',1);
    }

    /**
     * 处理用户登录事件
     * @param LoginEvent $event
     */
    public function handleUserLogin(LoginEvent $event)
    {

    }

    /**
     * 处理用户注销事件
     * @param $event
     */
    public function handleUserLogout(LogoutEvent $event)
    {

    }

    /**
     * 处理用户信息编辑事件
     * @param UpdatedEvent $event
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function handleUserUpdated(UpdatedEvent $event)
    {
        $user = $event->user;
        $this->IMBridge->portraitSet($user->user_id, $user->user_name, $user->user_avatar);
    }

    /**
     * 为订阅者注册监听器.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'App\Events\User\RegisterEvent',
            'App\Listeners\UserEventSubscriber@handleUserRegister'
        );

        $events->listen(
            'App\Events\User\LoginEvent',
            'App\Listeners\UserEventSubscriber@handleUserLogin'
        );

        $events->listen(
            'App\Events\User\LogoutEvent',
            'App\Listeners\UserEventSubscriber@handleUserLogout'
        );

        $events->listen(
            'App\Events\User\UpdatedEvent',
            'App\Listeners\UserEventSubscriber@handleUserUpdated'
        );
    }
}
