@extends('layout.h5')

@section('title')
    {{ $category['name'] }}
@endsection

@section('content')
    <div class="list-box">
        @if ($category['list_type'] == 1)
            @verbatim
                <div v-for="(info, index) in list" :key="index" class="list" @click="toDetail(info)">
                    <div v-if="isShow('title')" class="title">{{ info.title }}</div>
                    <div v-if="isShow('created_at') || isShow('author') || isShow('hits') || isShow('tag')" class="info flex">
                        <div v-if="isShow('created_at')" class="created_at">{{ info.created_at }}</div>
                        <div v-if="isShow('author')" class="author">作者：{{ info.author }}</div>
                        <div v-if="isShow('hits')" class="hits">阅读数：{{ info.hits }}</div>
                        <div v-if="isShow('tag')" class="tag">
                            <span v-for="(tag, index) in info.tag" :key="index">{{ tag }}</span>
                        </div>
                    </div>
                    <div v-if="isShow('summary')" class="summary">
                        {{ info.summary }}
                    </div>
                    <div v-if="isShow('cover') && info.cover" class="cover-box">
                        <img :src="info.cover" fit="cover" :style="coverStyle" class="cover" />
                    </div>
                </div>
            @endverbatim
        @elseif ($category['list_type'] == 2)
            @verbatim
                <div v-for="(info, index) in list" :key="index" class="list" @click="toDetail(info)">
                    <div v-if="isShow('cover') && info.cover" class="cover-box">
                        <img :src="info.cover" fit="cover" :style="coverStyle" class="cover" />
                    </div>
                    <div v-if="isShow('title')" class="title">{{ info.title }}</div>
                    <div v-if="isShow('created_at') || isShow('author') || isShow('hits') || isShow('tag')" class="info flex">
                        <div v-if="isShow('created_at')" class="created_at">{{ info.created_at }}</div>
                        <div v-if="isShow('author')" class="author">作者：{{ info.author }}</div>
                        <div v-if="isShow('hits')" class="hits">阅读数：{{ info.hits }}</div>
                        <div v-if="isShow('tag')" class="tag">
                            <span v-for="(tag, index) in info.tag" :key="index">{{ tag }}</span>
                        </div>
                    </div>
                    <div v-if="isShow('summary')" class="summary">
                        {{ info.summary }}
                    </div>
                </div>
            @endverbatim
        @elseif ($category['list_type'] == 3)
            @verbatim
                <div v-for="(info, index) in list" :key="index" class="list flex" @click="toDetail(info)">
                    <div v-if="isShow('cover')" class="cover-box flex-none">
                        <img :src="info.cover" fit="cover" :style="coverStyle" class="cover" />
                    </div>
                    <div class="flex-auto">
                        <div v-if="isShow('title')" class="title">{{ info.title }}</div>
                        <div v-if="isShow('summary')" class="summary">
                            {{ info.summary }}
                        </div>
                        <div class="info flex">
                            <div v-if="isShow('created_at')" class="created_at">{{ info.created_at }}</div>
                            <div v-if="isShow('author')" class="author">作者：{{ info.author }}</div>
                            <div v-if="isShow('hits')" class="hits">阅读数：{{ info.hits }}</div>
                            <div v-if="isShow('tag')" class="tag">
                                <span v-for="(tag, index) in info.tag" :key="index">{{ tag }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endverbatim
        @elseif ($category['list_type'] == 4)
            @verbatim
                <div v-for="(info, index) in list" :key="index" class="list flex" @click="toDetail(info)">
                    <div class="flex-auto">
                        <div v-if="isShow('title')" class="title">{{ info.title }}</div>
                        <div v-if="isShow('summary')" class="summary">
                            {{ info.summary }}
                        </div>
                        <div class="info flex">
                            <div v-if="isShow('created_at')" class="created_at">{{ info.created_at }}</div>
                            <div v-if="isShow('author')" class="author">作者：{{ info.author }}</div>
                            <div v-if="isShow('hits')" class="hits">阅读数：{{ info.hits }}</div>
                            <div v-if="isShow('tag')" class="tag">
                                <span v-for="(tag, index) in info.tag" :key="index">{{ tag }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-if="isShow('cover')" class="cover-box flex-none">
                        <img :src="info.cover" fit="cover" :style="coverStyle" class="cover" />
                    </div>
                </div>
            @endverbatim
        @endif
    </div>
@endsection

@section('scripts')
    <script type="text/javascript">
        var category = @json($category);
        new Vue({
            el: '.list-box',
            mixins: [ mixinList ],
            data: {
                list: [],
                isLoadList: true,
                loadListApi: 'pool/content/articles',
                getListParams: {
                    id: '{{ $category['article_category_id'] }}'
                },
                category: category
            },
            computed: {
                coverStyle() {
                    return {width: this.category.cover_size * document.body.clientWidth / 375 + 'px'}
                }
            },
            methods: {
                isShow(val) {
                    return this.category.list_fields.indexOf(val) >= 0
                },
                toDetail(info) {
                    if (info.content_type == 1) {
                        window.location.href = './article/detail?id=' + info.article_id
                    } else {
                        window.location.href = 'http://' + info.link
                    }
                }
            }
        })
    </script>
@endsection

@section('styles')
    <style type="text/css">
        .list {
            margin-bottom: 10px;
            background-color: #fff;
            padding-bottom: 10px;
            border-bottom: 1px solid #eeeeee;
        }
        .title {
            font-size: 16px;
            padding: 10px 10px 0;
        }
        .info {
            padding: 10px 5px 0;
            font-size: 12px;
            color: #999999;
        }
        .info>div {
            padding: 0 5px;
        }
        .info>div+div{
            border-left: solid 1px #ddd;
        }
        .summary {
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
        .cover {
            background-color: #F8F8F8;
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
