/**
 * 拼接签名字符串
 * @param params
 * @returns {string}
 */
function makeParamSource (params) {
    const keys = Object.keys(params || {}).sort()
    const arr = []
    keys.forEach(val => {
        let param = params[val]
        if (typeof param === 'object') {
            param = JSON.stringify(param)
        }
        arr.push(val + '=' + param)
    })
    return encodeURIComponent(arr.join(('&')))
}

/**
 * 签名
 * @param source
 * @returns {*}
 */
function sign (source) {
    return CryptoJS.enc.Base64.stringify(CryptoJS.HmacSHA1(source, settings.appSecret))
}

// create an axios instance
var request = axios.create({
    baseURL: settings.baseUrl, // url = base url + request url
    // withCredentials: true, // send cookies when cross-domain requests
    timeout: 5000 // request timeout
})

// request interceptor
request.interceptors.request.use(
    config => {
        // do something before request is sent

        config.headers[ 'SC-API-APP'] = settings.appId

        let data = config.data || {}
        data = Object.assign(data, config.params)
        const signSource = makeParamSource(data)
        config.headers[ 'SC-API-SIGNATURE'] = sign(signSource)

        return config
    },
    error => {
        // do something with request error
        console.log(error) // for debug
        return Promise.reject(error)
    }
)

// response interceptor
request.interceptors.response.use(
    /**
     * If you want to get http information such as headers or status
     * Please return  response => response
     */

    /**
     * Determine the request status by custom code
     * Here is just an example
     * You can also judge the status by HTTP Status Code
     */
    response => {
        const res = response.data

        // if the custom code is not 20000, it is judged as an error.
        if (!res.success) {
            alert(res.message || 'Error')
            return Promise.reject(new Error(res.message || 'Error'))
        } else {
            return res
        }
    },
    error => {
        alert(error.message)
        console.log('err: ' + error) // for debug
        return Promise.reject(error)
    }
)

var post = (url, data) => {
    return request({
        url: url,
        method: 'post',
        data
    })
}

var get = (url, params) => {
    return request({
        url: url,
        method: 'get',
        params
    })
}
