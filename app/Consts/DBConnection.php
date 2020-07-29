<?php


namespace App\Consts;


use Illuminate\Support\Facades\DB;

class DBConnection
{

    public static  function getTradeConnection(){
        return DB::connection('sc_trade');
    }

    public static function getUserConnection(){
        return DB::connection('sc_user');
    }

    public static function getPoolConnection(){
        return DB::connection('sc_pool');
    }

    public static function getMessageConnection(){
        return DB::connection('sc_message');
    }

    public static function getPermissionConnection(){
        return DB::connection('sc_permission');
    }

    public static function getStatisticsConnection(){
        return DB::connection('sc_statistics');
    }
}
