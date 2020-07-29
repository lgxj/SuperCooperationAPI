<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="@yield('description')">
    <meta name="keyword" content="@yield('keyword')">
    <title>
        @yield('title')
    </title>
    <style>
        html {
            position: relative;
            min-height: 100%;
        }
        body {
            font-size: .875rem;
        }
        * {
            margin: 0;
            padding: 0;
        }
    </style>
    <link href="/static/css/main.css" rel="stylesheet" type="text/css">
    @yield('styles')
</head>
<body>

<div class="container-fluid">
    <main role="main" class="pt-3">
        @yield('content')
    </main>
</div>

{{-- Footer --}}
<footer class="main-footer">

</footer>

<script type="text/javascript" src="{{asset('static/js/setting.js')}}"></script>
<script type="text/javascript" src="{{asset('static/js/vue.js')}}"></script>
<script type="text/javascript" src="{{asset('static/js/axios.min.js')}}"></script>
<script type="text/javascript" src="{{asset('static/js/crypto-js.js')}}"></script>
<script type="text/javascript" src="{{asset('static/js/request.js')}}"></script>
<script type="text/javascript" src="{{asset('static/js/mixin.js')}}"></script>

@yield('scripts')
</body>
</html>
