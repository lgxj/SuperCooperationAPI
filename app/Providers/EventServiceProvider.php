<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * 需要注册的订阅者类。
     *
     * @var array
     */
    protected $subscribe = [
        'App\Listeners\UserEventSubscriber',
        'App\Listeners\TaskOrder\TaskStartEventSubscriber',
        'App\Listeners\TaskOrder\TaskCompleteEventSubscriber',
        'App\Listeners\TaskOrder\TaskConfirmReceiveEventSubscriber',
        'App\Listeners\TaskOrder\TaskHelperCancelEventSubscriber',
        'App\Listeners\TaskOrder\TaskReceiveEventSubscriber',
        'App\Listeners\TaskOrder\TaskReverseStartEventSubscriber',
        'App\Listeners\TaskOrder\TaskEmployerCancelSubscriber',
        'App\Listeners\TaskOrder\TaskDeliveryEventSubscriber',
        'App\Listeners\TaskOrder\TaskUpdateEventSubscriber',
        'App\Listeners\TaskOrder\TaskAddEventSubscriber',
        'App\Listeners\TaskOrder\TaskRefuseDeliverySubscriber',
        'App\Listeners\TaskOrder\TaskHelperCompensatePayEventSubscriber',
        'App\Listeners\Funds\WithDrawSubscriber'
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
