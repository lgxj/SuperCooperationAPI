<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, viewport-fit=cover">
    <title>支付宝授权</title>
    <style type="text/css">
        body {
            margin: 0;
            font-size: 12px;
            background: #fff;
            color: #333;
        }

        .title {
            font-size: 16px;
            text-align: center;
            background-color: #ff8e08;
            color: #FFFFFF;
            line-height: 40px;
        }

        .content {
            padding: 100px 0;
            font-size: 14px;
            text-align: center;
        }

        .button {
            font-size: 16px;
            text-align: center;
        }
        .auth-success {
            display: block;
            width: 250px;
            height: 250px;
            margin: 140px auto 0;
        }
    </style>
</head>
<body>
<div class="title">支付宝授权</div>
<div class="content">
    @if ($status == 1)
    <img class="auth-success" src="/static/imgs/icon/auth-success.png">
    @else
    <img class="auth-success" src="/static/imgs/icon/auth-fail.png">
    @endif
</div>
<!--    <div class="button"><a href="#">回到APP</a></div>-->
</body>
</html>
