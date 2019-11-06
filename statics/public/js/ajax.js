;!function (window) {
    //设置ajax当前状态(是否可以发送);
    var ajaxStatus = true;
    window.aj = {};

    aj.get = function (url, data, success) {
        ajax("get", url, data, success);
    };

    aj.post = function (url, data, success) {
        ajax("POST", url, data, success);
    };


    function ajax(type, url, data, success) {

        /*判断是否可以发送请求*/
        if (!ajaxStatus) {
            return false;
        }
        ajaxStatus = false;//禁用ajax请求
        /*正常情况下1秒后可以再次多个异步请求，为true时只可以有一次有效请求（例如添加数据）*/

        setTimeout(function () {
            ajaxStatus = true;
        }, 1000);

        var index;
        $.ajax({
            'type': type,
            'url': url,
            'data': data,
            'cache': false,
            'async': true,
            'success': success,
			'dataType': "json",
            'beforeSend': function () {
                index = layer.load(1);
            },
            'complete': function () {
                layer.close(index);
            }
        });
    }
}(window);