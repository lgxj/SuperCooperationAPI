<p align="center">
<img src="https://www.250.cn/static/250/images/img/logo2.png" width="400">
</p>

<p align="left">
<a href="https://250.cn">
<img src="https://www.250.cn/static/250/images/logo.png" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework">
<img src="https://poser.pugx.org/laravel/framework/license.svg" alt="License">
</a>
</p>

## About 流光星际

流光星际（湖北）科技有限公司，成立于2019年，我们公司致力于互联网技术的开发与运用，从而实现指尖上互联网的人机交互，为人们生活的方方面面带来便利。我们以卓越的品质、专业的技术服务实力，为不同群体的用户提供更优质的服务。

- [我们的官网](https://250.cn).

##一、安装环境
1. 操作系统 windows/linux
2. 语言版本 php 7.0及以上
3. Mysql   mysql 5及以上
4. redis   redis 2.6及以上
5. ffmpeg  音频转码
6. supervisord 脚本进程管理工具

##二、框架
1. php laravel 6.0及以上
2. vue element-admin (后台管理)
3. app uniapp(ios/android/小程序)

##三、第三方服务说明
1. 实名认证（腾讯云）
2. 客服与聊天（腾讯IM免费）
3. 文件存储（阿里云OSS）
4. 短信服务（阿里云）
5. 附近地图（腾讯地点云免费）
6. 推送服务（个推免费）
7. 语音服务（讯飞语音）

##四、开发架构
1. 前后端分离（前端一个仓库，PHP核心接口一个仓库，app一个仓库）
2. 基于Restful风格设计接口
3. 基于MVC+Service进行业务开发
4. 基于业务模块独立架构、独立部署设计开发
5. 基于Mysql+Innodb设计数据库设计，处理核心事务一致性
6. 垂直分库，分为交易库、基础库、用户库、权限库，消息库
7. 基于laravel事件/队列解耦业务
8. 开发环境/线上环境独立配置

##五、域名说明：
1. 开源后台管理 (https://sc-admin.250.cn)
2. app端访问接口域名(https://sc-api.250.cn)
3. 后台管理访问接口域名(https://sc-admin-api.250.cn)
4. h5页面域名（https://sc.250.cn）
5. laravel 事件访问(https://sc.250.cn/redirect/horizon?token=eafai7ybc892ab2af)
6. laravel 日志访问（https://sc.250.cn/redirect/logs?token=eafai7ybc892ab2af）

##六、源码下载
1. Vue后台管理前端仓库开源
   <p>码云地址 https://gitee.com/lgxj_open_source/SuperCooperationAdmin </p>
   <p>Github地址 https://github.com/lgxj/SuperCooperationAdmin </p>
2. PHP后台管理仓库开源
   <p>码云地址 https://gitee.com/lgxj_open_source/SuperCooperationAPI  </p>
   <p>Github地址 https://github.com/lgxj/SuperCooperationAPI </p>
3. App端仓库收费8万(IOS/Android)，包含所有收费。如有需要联系客服
4. 只需数据库表和文档收费1万（赞助开源）。如有需要联系客服

##七、后台管理PC体验
 [访问后台](https://sc-admin.250.cn)

 1. 用户名：admin
 2. 密 码：123456


##八、支付配置
1. 微信App支付：微信开放平台注册App应用，微信商户平台注册商户号
2. 支付宝App支付：支付宝开放平台注册App应用
3. QQ登录：QQ互联注册应用



##九、产品说明
 &nbsp; &nbsp;&nbsp;&nbsp;全民帮帮是一款通用性的互相帮助、跑腿、中介、同城等综合性业务的APP开源软件，具有一定通用性、普惠性，互助性，全民性，全国性。全民帮帮开源是广大中小创业者的福音，帮助开发团队减少研发成本，缩短运营时间，
节约资源，减少试错成本。

##十、流程概要
 &nbsp; &nbsp;&nbsp;&nbsp;全民帮帮具有两个可以互换身份的用户角色，即雇主和帮手。雇主发布有偿任务（悬赏或竞价），帮手经过实名认证后，进行抢单或者报价，帮手获得任务后，在一定时间内完成雇主交予任务，帮手获得一定收益，
同时每笔任务平台获取一定抽成和服务费。任务完成后帮手和雇主可以互相评价，系统根据评分来判定雇主和帮手服务等级。

##十一、核心功能点
1. 雇主相关：雇主发布任务（语音发布与文字发布），取消任务，同意任务延期，确认完成任务，确认帮手，评价帮手，联系帮手，发单管理
2. 帮手相关：立即抢单（悬赏任务），报价（竞价任务），修改报价，取消任务，延期完成任务，交付任务，评价雇主，接单管理
3. 地图相关：附近的任务，附近的帮手，任务与帮手地图切换，任务文字或语音搜索，不同地区地图切换
4. 任务大厅：任务搜索、任务列表，任务状态，任务操作
5. 个人中心：收益，退款，提现，收支记录，地址库管理，意见反馈，客服服务 ，主页，技能展示，标签管理，账户信息
6. 消息中心：系统消息，聊天服务，订单消息
7. 平台收费：每笔任务收取指定比例抽成，再就是服务费（加急，保险，人脸接单）
8. 支付中心：微信支付、支付宝支付，余额支付
9. 登录中心：微信登录，QQ登录，手机号码登录
10. 其它：文章发布系统，任务统计，退款，赔偿体系

##十二、收费说明
1. Vue后台管理前端仓库开源
   <p>码云地址 https://gitee.com/lgxj_open_source/SuperCooperationAdmin </p>
   <p>Github地址 https://github.com/lgxj/SuperCooperationAdmin </p>
2. PHP后台管理仓库开源
   <p>码云地址 https://gitee.com/lgxj_open_source/SuperCooperationAPI  </p>
   <p>Github地址 https://github.com/lgxj/SuperCooperationAPI </p>
3. App端仓库收费8万(IOS/Android)，包含所有收费。如有需要联系客服
4. 只需数据库表和文档收费1万（赞助开源）。如有需要联系客服

##十三、联系客服
1. 微信：hao_are
2. 电话：13868170930


##十四、host配置
    后台管理业务接口配置


    server {
        listen        80;
        server_name sc-admin-api.250.cn;
        root   "/home/web/SuperCooperationApi/public";

        add_header Access-Control-Allow-Origin *;
        add_header Access-Control-Allow-Credentials true;
        add_header Access-Control-Allow-Methods 'GET, DELETE,PUT,POST, OPTIONS';
        add_header Access-Control-Allow-Headers 'DNT,X-Mx-ReqToken,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Authorization,SC-API-APP,SC-API-SIGNATURE,SC-ACCESS-TOKEN,SC-SUB-ID';
        add_header Access-Control-Max-Age 3600;
        if ($request_method = OPTIONS){
            return 200;
        }
        location / {
            index index.php index.html;
            autoindex  off;
            try_files $uri $uri/ /index.php?$query_string;

        }

        location ~ \.php(.*)$ {
            fastcgi_pass   127.0.0.1:9000;
            fastcgi_index  index.php;
            fastcgi_split_path_info  ^((?U).+\.php)(/?.+)$;
            fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
            fastcgi_param  PATH_INFO  $fastcgi_path_info;
            fastcgi_param  PATH_TRANSLATED  $document_root$fastcgi_path_info;
            include        fastcgi_params;
        }
    }

    核心业务接口配置


    server {
        listen        80;
        server_name sc-api.250.cn;
        root   "/home/web/SuperCooperationApi/public";

        add_header Access-Control-Allow-Origin *;
        add_header Access-Control-Allow-Credentials true;
        add_header Access-Control-Allow-Methods 'GET, DELETE,PUT,POST, OPTIONS';
        add_header Access-Control-Allow-Headers 'DNT,X-Mx-ReqToken,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Authorization,SC-API-APP,SC-API-SIGNATURE,SC-ACCESS-TOKEN';
        add_header Access-Control-Max-Age 3600;
        if ($request_method = OPTIONS){
            return 200;
        }
        location / {
            index index.php index.html;
            autoindex  off;
            try_files $uri $uri/ /index.php?$query_string;

        }

        location ~ \.php(.*)$ {
            fastcgi_pass   127.0.0.1:9000;
            fastcgi_index  index.php;
            fastcgi_split_path_info  ^((?U).+\.php)(/?.+)$;
            fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
            fastcgi_param  PATH_INFO  $fastcgi_path_info;
            fastcgi_param  PATH_TRANSLATED  $document_root$fastcgi_path_info;
            include        fastcgi_params;
        }
    }


    H5 Web端域名配置
    server {
        listen        80;
        server_name sc.250.cn;
        root   "/home/web/SuperCooperationApi/public";

        add_header Access-Control-Allow-Origin *;
        add_header Access-Control-Allow-Credentials true;
        add_header Access-Control-Allow-Methods 'GET, DELETE,PUT,POST, OPTIONS';
        add_header Access-Control-Allow-Headers 'DNT,X-Mx-ReqToken,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Authorization,SC-API-APP,SC-API-SIGNATURE,SC-ACCESS-TOKEN';
        add_header Access-Control-Max-Age 3600;
        if ($request_method = OPTIONS){
            return 200;
        }
        location / {
            index index.php index.html;
            autoindex  off;
            try_files $uri $uri/ /index.php?$query_string;

        }

        location ~ \.php(.*)$ {
            fastcgi_pass   127.0.0.1:9000;
            fastcgi_index  index.php;
            fastcgi_split_path_info  ^((?U).+\.php)(/?.+)$;
            fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
            fastcgi_param  PATH_INFO  $fastcgi_path_info;
            fastcgi_param  PATH_TRANSLATED  $document_root$fastcgi_path_info;
            include        fastcgi_params;
        }
    }
    
    后台访问接口域名配置
    server {
        listen        80;
        server_name sc-admin.250.cn;
        root   "/home/web/SuperCooperationAdmin/dist";

        add_header Access-Control-Allow-Origin *;
        add_header Access-Control-Allow-Credentials true;
        add_header Access-Control-Allow-Methods 'GET, DELETE,PUT,POST, OPTIONS';
        add_header Access-Control-Allow-Headers 'DNT,X-Mx-ReqToken,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Authorization,SC-API-APP,SC-API-SIGNATURE,SC-ACCESS-TOKEN';
        add_header Access-Control-Max-Age 3600;
        if ($request_method = OPTIONS){
            return 200;
        }
        location / {
            index  index.html;
            autoindex  off;
            try_files $uri $uri/  /index.html;
        }

    }


##十五.supervisord配置

    [program:laravel_horizon]
    command=php artisan horizon
    user=root
    stdout_logfile=/var/log/supervisor/laravel-horizon-stdout.log
    stderr_logfile=/var/log/supervisor/laravel-horizon-stderr.log
    directory=/home/web/SuperCooperationApi/
    process_name=%(program_name)s_%(process_num)s
    autostart=true
    autorestart=true
    numprocs=2
    stderr_logfile_maxbytes=10MB
    stopwaitsecs=3600

    [program:laravel_queue_listen]
    command=php artisan queue:listen
    user=root
    stdout_logfile=/var/log/supervisor/laravel-queue-stdout.log
    stderr_logfile=/var/log/supervisor/laravel-queue-stderr.log
    directory=/home/web/SuperCooperationApi/
    process_name=%(program_name)s_%(process_num)s
    autostart=true
    autorestart=true
    numprocs=2
    stderr_logfile_maxbytes=10MB

##十六、crontab配置
1. 脚本统访问入口 */1 * * * * /usr/local/bin/php /var/www/SuperCooperationApi/artisan  schedule:run  >> /var/log/cron/laravel.log 2>&1
2. 定期服务器日志 59 23 */3 * * /var/www/SuperCooperationApi/clean.sh >> /var/log/cron/clean.log 2>&1

##十七、初始化数据
1. 全国地址库数据:docs/address.sql
2. 默认用户标签:docs/label.sql
3. 默认任务分类:docs/order_category.sql


##十八、腾讯地点云
1. 访问地址：https://lbs.qq.com/service/placeCloud/placeCloudGuide/cloudOverview
2. 具体说明：docs/全民帮帮开源核心业务流程说明.docs


##十九、公司其它产品说明
