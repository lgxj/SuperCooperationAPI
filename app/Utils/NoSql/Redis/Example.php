<?php

// $a = Redis::connection('default')->command("geodist",['location','shanghai','hangzhou','km']);
// $b = Redis::connection('geo')->geoadd("location","116","39",'shanghai');
//$b = Redis::connection('geo')->geoadd("location","113","30",'hangzhou');
// $b = Redis::connection('geo')->geodist("location","shanghai","hangzhou",'km');
//$c = Redis::connection('geo')->geohash("location","shanghai","hangzhou");

$store = new  \App\Utils\NoSql\Redis\Table\SHashTable(['suqian']);
// $store->insert(4,['goods_name'=>'姨s夫','stock'=>12]);
// $store->insert(2,['goods_name'=>'姨夫','stock'=>2]);
// $store->update(3,['goods_name'=>'姨夫3','stock'=>3]);
//print_r($store->findAll());
// print_r($store->contains(2));
// print_r($store->deleteAll());
