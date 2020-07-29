@extends('layout.h5')

@section('title')
    {{ $info['title'] }}
@endsection

@section('content')
    <div class="box">
        <div class="detail-box">
            @if (articleShowField('title', $category['detail_fields']))
                <div v-if="isShow('title')" class="title">{{ $info['title'] }}</div>
            @endif

            @if (articleShowField(['created_at', 'author', 'hits', 'tag'], $category['detail_fields']))
                <div class="info flex">
                    @if (articleShowField('created_at', $category['detail_fields']))
                        <div class="created_at">{{ $info['created_at'] || '现在' }}</div>
                    @endif
                    @if (articleShowField('author', $category['detail_fields']))
                        <div class="author">作者：{{ $info['author'] }}</div>
                    @endif
                    @if (articleShowField('hits', $category['detail_fields']))
                        <div class="hits">阅读数：{{ $info['hits'] ?: 0 }}</div>
                    @endif
                    @if (articleShowField('tag', $category['detail_fields']))
                        <div class="tag">
                            @foreach ($info['tag'] as $tag)
                                <span>{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            @if (articleShowField('summary', $category['detail_fields']))
                <div class="summary">
                    {{ $info['summary'] }}
                </div>
            @endif

            @if (articleShowField('content', $category['detail_fields']))
                <div class="content">{!! $info['content'] !!}</div>
            @endif

            @if (articleShowField('photos', $category['detail_fields']) && count($info['photos']))
                <div class="photos">
                    <div class="swiper-container" style="height: {{ $category['cover_size'] / 3.75 }}vw">
                        <div class="swiper-wrapper">
                            @foreach ($info['photos'] as $photo)
                                <div class="swiper-slide"><img src="{{ $photo['url'] }}" class="photo"></div>
                            @endforeach
                        </div>

                        <!-- 如果需要分页器 -->
                        <div class="swiper-pagination"></div>

                        <!-- 如果需要导航按钮 -->
                        <div class="swiper-button-prev"></div>
                        <div class="swiper-button-next"></div>
                    </div>
                </div>
            @endif
        </div>

        @if (articleShowField('relation', $category['detail_fields']))
            <div class="list-box">
                <div class="sub-title">
                    相关文章
                    @if (count($list))
                        <a href="../article?id={{ $info['category_id'] }}" class="link">更多>></a>
                    @endif
                </div>
                <div>
                    @if (count($list))
                        @foreach ($list as $info)
                            <a href="{{ $info['content_type'] == 1 ? '?id=' . $info['article_id'] : ($info['link'] ? 'http://' . $info['link'] : '#') }}">
                                @if ($category['list_type'] == 1 || $category['list_type'] == 2)
                                    <div class="list">
                                        @if ($category['list_type'] == 2 && articleShowField('cover', $category['list_fields']) && $info['cover'])
                                            <div class="cover-box">
                                                <img src="{{ $info['cover'] }}" fit="cover"
                                                     style="width: {{ $category['cover_size'] / 3.75 }}vw"
                                                     class="cover"/>
                                            </div>
                                        @endif
                                        @if (articleShowField('title', $category['list_fields']))
                                            <div class="title">{{ $info['title'] }}</div>
                                        @endif
                                        @if (articleShowField(['created_at', 'author', 'hits', 'tag'], $category['list_fields']))
                                            <div class="info flex">
                                                @if (articleShowField('created_at', $category['list_fields']))
                                                    <div class="created_at">{{ $info['created_at'] || '现在' }}</div>
                                                @endif
                                                @if (articleShowField('author', $category['list_fields']))
                                                    <div class="author">作者：{{ $info['author'] }}</div>
                                                @endif
                                                @if (articleShowField('hits', $category['list_fields']))
                                                    <div class="hits">阅读数：{{ $info['hits'] ?: 0 }}</div>
                                                @endif
                                                @if (articleShowField('tag', $category['list_fields']))
                                                    <div class="tag">
                                                        @foreach ($info['tag'] as $tag)
                                                            <span>{{ $tag }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                        @if (articleShowField('summary', $category['list_fields']))
                                            <div class="summary">
                                                {{ $info['summary'] }}
                                            </div>
                                        @endif

                                        @if ($category['list_type'] == 1 && articleShowField('cover', $category['list_fields']) && $info['cover'])
                                            <div class="cover-box">
                                                <img src="{{ $info['cover'] }}" fit="cover"
                                                     style="width: {{ $category['cover_size'] / 3.75 }}vw"
                                                     class="cover"/>
                                            </div>
                                        @endif
                                    </div>
                                @elseif ($category['list_type'] == 3 || $category['list_type'] == 4)
                                    <div class="list">
                                        @if ($category['list_type'] == 3 && articleShowField('cover', $category['list_fields']) && $info['cover'])
                                            <div class="cover-box flex-none">
                                                <img src="{{ $info['cover'] }}" fit="cover"
                                                     style="width: {{ $category['cover_size'] / 3.75 }}vw"
                                                     class="cover"/>
                                            </div>
                                        @endif
                                        <div class="flex-auto">
                                            @if (articleShowField('title', $category['list_fields']))
                                                <div class="title">{{ $info['title'] }}</div>
                                            @endif
                                            @if (articleShowField('summary', $category['list_fields']))
                                                <div class="summary">
                                                    {{ $info['summary'] }}
                                                </div>
                                            @endif
                                            @if (articleShowField(['created_at', 'author', 'hits', 'tag'], $category['list_fields']))
                                                <div class="info flex">
                                                    @if (articleShowField('created_at', $category['list_fields']))
                                                        <div class="created_at">{{ $info['created_at'] || '现在' }}</div>
                                                    @endif
                                                    @if (articleShowField('author', $category['list_fields']))
                                                        <div class="author">作者：{{ $info['author'] }}</div>
                                                    @endif
                                                    @if (articleShowField('hits', $category['list_fields']))
                                                        <div class="hits">阅读数：{{ $info['hits'] ?: 0 }}</div>
                                                    @endif
                                                    @if (articleShowField('tag', $category['list_fields']))
                                                        <div class="tag">
                                                            @foreach ($info['tag'] as $tag)
                                                                <span>{{ $tag }}</span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                        @if ($category['list_type'] == 4 && articleShowField('cover', $category['list_fields']) && $info['cover'])
                                            <div class="cover-box flex-none">
                                                <img src="{{ $info['cover'] }}" fit="cover"
                                                     style="width: {{ $category['cover_size'] / 3.75 }}vw"
                                                     class="cover"/>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </a>
                        @endforeach
                    @else
                        <p class="empty">没有相关内容</p>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script src="/static/libs/swiper/swiper.min.js"></script>
    <script type="text/javascript">
        var mySwiper = new Swiper('.swiper-container', {
            loop: true, // 循环模式选项
            // 如果需要分页器
            pagination: {
                el: '.swiper-pagination',
            },

            // 如果需要前进后退按钮
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            }
        })
    </script>
@endsection

@section('styles')
    <link rel="stylesheet" href="/static/libs/swiper/swiper.min.css">
    <style type="text/css">
        .box {
            background-color: #F8F8F8;
        }

        .swiper-container {
            background-color: #F8F8F8;
            padding: 5px 0;
        }

        .swiper-button-prev, .swiper-button-next {
            color: rgba(0, 0, 0, .2);
        }

        .detail-box {
            background-color: #fff;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .title {
            font-size: 18px;
            padding: 10px 10px 0;
        }

        .info {
            padding: 10px 5px 0;
            font-size: 12px;
            color: #999999;
        }

        .info > div {
            padding: 0 5px;
        }

        .info > div + div {
            border-left: solid 1px #ddd;
        }

        .detail-box .summary {
            margin: 10px 10px 0;
            padding: 10px;
            font-size: 13px;
            line-height: 1.3;
            background-color: #f8f8f8;
            border-right: 4px;
        }

        .content {
            padding: 10px 10px 0;
            font-size: 14px;
            line-height: 1.3;
        }

        .photos {
            margin-top: 10px;
            text-align: center;
        }

        .photo {
            max-width: 100%;
            max-height: 100%;
            background-color: #F8F8F8;
        }

        .sub-title {
            font-size: 16px;
            padding: 10px 10px 0;
        }

        .sub-title .link {
            float: right;
            font-size: 12px;
            color: #3A71A8;
            line-height: 14px;
        }

        .tag span + span {
            margin-left: 5px;
        }

        .list-box {
            background-color: #ffffff;
        }

        .list-box .list {
            margin-top: 10px;
            background-color: #fff;
            padding-bottom: 10px;
            border-top: 1px solid #eeeeee;
        }

        .list-box .title {
            font-size: 16px;
            padding: 10px 10px 0;
        }

        .list-box .summary {
            padding: 10px 10px 0;
            font-size: 14px;
            line-height: 1.3;
        }

        .cover-box {
            margin-top: 10px;
            text-align: center;
            background-color: #f8f8f8;
            padding: 2px;
            overflow: hidden;
        }

        .list-box a {
            color: #000000;
        }

        .empty {
            padding: 40px 20px;
            text-align: center;
            color: #666;
            font-size: 12px;
        }

        .flex {
            display: flex;
        }

        .flex-auto {
            flex: auto;
        }

        .flex-none {
            flex: none;
        }
    </style>
@endsection
