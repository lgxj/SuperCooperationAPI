var mixinList = {
    data: {
        isLoadList: false,
        isMoreLoad: true,  // 是否显示加载更多
        loadingImg: false,  // 加载更多时显示loading图
        loadLastText: false, // 到底了
        definePageNum: 1,// 默认加载页数
        definePageSize: 10, // 默认每页数量
        totals: null, // 用来存放总数量
        list: []
    },
    created() {
        var _this = this;
        if (this.isLoadList) {
            window.addEventListener('scroll', function(){
                var scr = document.documentElement.scrollTop || document.body.scrollTop; // 向上滚动的那一部分高度
                var clientHeight = document.documentElement.clientHeight; // 屏幕高度也就是当前设备静态下你所看到的视觉高度
                var scrHeight = document.documentElement.scrollHeight || document.body.scrollHeight; // 整个网页的实际高度，兼容Pc端
                if(scr + clientHeight + 10 >= scrHeight){
                    if(_this.isMoreLoad){ //this.isMoreLoad控制滚动是否加载更多
                        _this.definePageNum = _this.definePageNum + 1; // 加载更多是definePageNum+1
                        _this.loadData();
                    }else{
                        return;
                    }
                }
            });
            this.loadList()
        }
    },
    methods: {
        loadList() {
            // 防止多次加载
            if(this.loadingImg){
                return;
            }
            this.loadingImg = true;
            get(this.loadListApi, Object.assign(this.getListParams, {
                    'page': this.definePageNum,
                    'limit': this.definePageSize
                })
            ).then((res) =>{
                if (res.success) {
                    this.definePageNum ++
                    this.totals = res.data.total;
                    this.list = res.data.list
                    if(this.totals - this.definePageNum*this.definePageSize > 0){
                        this.isMoreLoad = true;
                    }else{
                        this.isMoreLoad = false;
                        this.loadLastText = true;
                    }
                    this.loadingImg = false;
                }
            })
        }
    }
}
