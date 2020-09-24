if (device.ie && device.ie < 8) {
    layer.alert('本站最低支持ie8，您当前使用的是古老的 IE' + device.ie + '，你丫的电脑该升级啦！！！')
}
var treeMobile = $('.site-tree-mobile'), shadeMobile = $('.site-mobile-shade');
treeMobile.on('click', function () {
    $('body').addClass('site-mobile')
});
shadeMobile.on('click', function () {
    $('body').removeClass('site-mobile')
});



/*
** 时间戳转换成指定格式日期
** eg.
** dateFormat(11111111111111, 'Y年m月d日 H时i分')
** → "2322年02月06日 03时45分"
*/
var dateFormat = function (timestamp, formats) {
    timestamp=parseInt(timestamp)*1000;
    // formats格式包括
    // 1. Y-m-d
    // 2. Y-m-d H:i:s
    // 3. Y年m月d日
    // 4. Y年m月d日 H时i分
    formats = formats || 'Y-m-d';

    var zero = function (value) {
        if (value < 10) {
            return '0' + value;
        }
        return value;
    };

    var myDate = timestamp? new Date(timestamp): new Date();

    var year = myDate.getFullYear();
    var month = zero(myDate.getMonth() + 1);
    var day = zero(myDate.getDate());

    var hour = zero(myDate.getHours());
    var minite = zero(myDate.getMinutes());
    var second = zero(myDate.getSeconds());

    return formats.replace(/Y|m|d|H|i|s/ig, function (matches) {
        return ({
            Y: year,
            m: month,
            d: day,
            H: hour,
            i: minite,
            s: second
        })[matches];
    });
};