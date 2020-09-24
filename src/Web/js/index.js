laytpl.cutDate = function (d) {
    return d.split(' ')[0];
};
laytpl.auth = function (d) {
    return true;
};
laytpl.rep_node = function (node) {
    return node.replace(/\//g, '-');
};
laytpl.toDateString = function (d, format) {
    var date = new Date(d || new Date())
        , ymd = [
        this.digit(date.getFullYear(), 4)
        , this.digit(date.getMonth() + 1)
        , this.digit(date.getDate())
    ]
        , hms = [
        this.digit(date.getHours())
        , this.digit(date.getMinutes())
        , this.digit(date.getSeconds())
    ];

    format = format || 'yyyy-MM-dd HH:mm:ss';
    return format.replace(/yyyy/g, ymd[0]).replace(/MM/g, ymd[1]).replace(/dd/g, ymd[2]).replace(/HH/g, hms[0]).replace(/mm/g, hms[1]).replace(/ss/g, hms[2]);
};

laytpl.digit = function (num, length, end) {
    var str = '';
    num = String(num);
    length = length || 2;
    for (var i = num.length; i < length; i++) {
        str += '0';
    }
    return num < Math.pow(10, length) ? str + (num | 0) : num;
};

function htmlspecialchars(str) {
    var s = "";
    if (str.length == 0) return "";
    for (var i = 0; i < str.length; i++) {
        switch (str.substr(i, 1)) {
            case "<":
                s += "&lt;";
                break;
            case ">":
                s += "&gt;";
                break;
            case "&":
                s += "&amp;";
                break;
            case " ":
                if (str.substr(i + 1, 1) == " ") {
                    s += " &nbsp;";
                    i++;
                } else s += " ";
                break;
            case "\"":
                s += "&quot;";
                break;
            case "\n":
                s += "<br>";
                break;
            default:
                s += str.substr(i, 1);
                break;
        }
    }
    return s;
}


function htmlspecialchars_decode(str) {
    str = str.replace(/&amp;/g, '&');
    str = str.replace(/&lt;/g, '<');
    str = str.replace(/&gt;/g, '>');
    str = str.replace(/&quot;/g, "''");
    str = str.replace(/&#039;/g, "'");
    return str;
}


function arr_list_key(arr, key) {
    var _all = [];
    $.each(arr, function (i, p) {
        _all.push(p[key]);
    });
    return _all;
}

function bytesToSize(bytes) {
    if (bytes === 0) return '0 B';
    var k = 1024;
    sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    i = Math.floor(Math.log(bytes) / Math.log(k))
    return (bytes / Math.pow(k, i)).toPrecision(3) + ' ' + sizes[i];
}

function byteToSize(bytes) {
    if (bytes === 0) return '0';
    var k = 1000;
    sizes = ['', 'K', 'W'];
    i = Math.floor(Math.log(bytes) / Math.log(k));
    if (bytes < 1000) {
        return bytes;
    } else {
        return (bytes / Math.pow(k, i)).toPrecision(3) + ' ' + sizes[i];
    }

}

function toThousands(num) {
    var num = (num || 0).toString(), result = '';
    while (num.length > 3) {
        result = ',' + num.slice(-3) + result;
        num = num.slice(0, num.length - 3);
    }
    if (num) {
        result = num + result;
    }
    return result;
};


$('body').on('click', '#menu-top [data-menu]', function () {
    var m = $(this).attr('data-menu');
    $('[data-menu-node]').hide();
    $('[data-menu-node=' + m + ']').show();
    $('#menu-left').find('[data-menu-node=' + m + ']:first').find('[data-open]:first').click();
}).on('click', '[data-reset]', function () {
    $(this).parents('from').reset();
}).on('click', '[data-layer]', function () {
    var _this = $(this);
    var type = _this.attr('data-layer') || 'open';
    var title = _this.attr('data-title') || '操作确认';
    var msg = _this.attr('data-msg') || '确认进行本操作？';
    var formType = _this.attr('data-form-type') || 1;
    var _key = _this.attr('data-key') || '';
    var _field = _this.attr('data-field') || '';
    var _value = _this.attr('data-value') || '';
    var _update = _this.attr('data-update') || '';
    var _id = _this.attr('data-table') || '';
    var affirm_url = _this.attr('data-action') || '';

    if (type == 'open') {
        layer.open({title: title, content: msg});
    } else if (type == 'update') {
        layer.alert(msg, {title: title}, function (index) {
            if (affirm_url) {
                $.post(affirm_url, {key: _key, update: _update, field: _field, value: _value}, function (e) {
                    console.log(e)
                    layer.close(index);
                    if (e.resp_code == "0000") {
                        console.log(_id)
                        if (_id) {
                            var table = eval(_id);
                            table.reload();
                        }
                        layer.msg(e.msg, {icon: 1});
                    } else {
                        layer.msg(e.msg, {icon: 2, amin: 6});
                    }
                })
            }
        });
    } else if (type == 'alert_1') {
        layer.alert(msg, {title: title}, function (index) {
            if (affirm_url) {
                $.get(affirm_url, function (e) {
                    console.log(e)
                    layer.close(index);
                    if (e.resp_code == "0000") {
                        layer.msg(e.msg, {icon: 1});
                    } else {
                        layer.msg(e.msg, {icon: 2, amin: 6});
                    }0
                })
            }
        });
    } else if (type == 'confirm') {
        layer.confirm(msg, {title: title});
    } else if (type == 'msg') {
        layer.msg(msg);
    } else if (type == 'tips') {
        layer.tips(msg, _this, {});
    } else if (type == 'prompt') {
        layer.prompt({
            formType: formType,
            value: _value,
            title: title
        }, function (value, index, elem) {
            if (affirm_url && _field) {
                $.post(affirm_url, {field: _field, value: value}, function (e) {
                    console.log(e)
                    layer.close(index);
                    if (e.resp_code == "0000") {
                        if (_id) {
                            var table = eval(_id);
                            table.reload();
                        }
                        layer.msg(e.msg, {icon: 1});
                    } else {
                        layer.msg(e.msg, {icon: 2, amin: 6});
                    }
                })
            }
        });
    }
}).on('click', '[data-open]', function () {
    var m = $(this).attr('data-open');
    if (tablse == true) {
        if ($('[lay-id=' + m + ']').length > 0) {
            element.tabChange('admin-tab', m);
        } else {
            var title = $(this).text();
            var url = $(this).attr('data-url');
            index_load = layer.load(2, {
                shade: [0.1, '#2F4056'],
            });
            $.ajax({
                url: url,
                type: 'PUT',
                timeout: 15000,
                error: function (e) {
                    setTimeout(function () {
                        layer.close(index_load)
                    },200)
                    layer.open({
                        type: 2, anim: 6, title: e.statusText, area: ['700px', '450px'],
                        fixed: false, maxmin: true, shadeClose: true, content: url
                    });
                },
                success: function (e) {
                    setTimeout(function () {
                        layer.close(index_load)
                    },200)

                    if (e.resp_code == "0401") {
                        layer.msg('登陆失效', {icon: 2}, function () {
                            window.location.href = error_url
                        });
                    } else if (e.resp_code != "0000") {
                        layer.msg(e.msg, {icon: 2, anim: 6})
                    } else {
                        var templet = '';
                        if (e.data.templet) {
                            templet = e.data.templet;
                        }
                        if (e.data.tpl) {
                            element.tabAdd('admin-tab', {
                                title: title
                                , content: templet + '<div class="layui-main">' + e.data.tpl + '</div>'
                                , id: m
                            });
                            element.tabChange('admin-tab', m);
                            element.render('tab');

                        } else {
                            element.tabAdd('admin-tab', {
                                title: title
                                , content: templet + '<div class="layui-main">' + e.response + '</div>'
                                , id: m
                            });
                            element.tabChange('admin-tab', m);
                        }

                        $("#admin-tab-title [lay-id=" + m + "]").contextMenu({
                            menu: [
                                {
                                    text: "刷新当前标签",
                                    callback: function () {
                                        var l = $('#admin-tab-title').find('li').length;
                                        element.tabDelete('admin-tab', m);
                                        if(l>1){
                                            $("[data-open=" + m + "]").click();
                                        }
                                    }
                                },{
                                    text: "关闭当前标签",
                                    callback: function () {
                                        element.tabDelete('admin-tab', m);
                                    }
                                },
                                {
                                    text: "关闭其他标签",
                                    callback: function () {
                                        layui.each($("#admin-tab-title [lay-id]"), function (e, v) {
                                            if (m != $(v).attr('lay-id')) {
                                                element.tabDelete('admin-tab', $(v).attr('lay-id'));
                                            }
                                        })
                                    }
                                },
                                {
                                    text: "关闭所有标签",
                                    callback: function () {
                                        layui.each($("#admin-tab-title [lay-id]"), function (e, v) {
                                            element.tabDelete('admin-tab', $(v).attr('lay-id'));
                                        })
                                    }
                                }
                            ]
                        });
                    }
                }
            });
        }
    } else {
        var title = $(this).text();
        var url = $(this).attr('data-url');
        index_load = layer.load(2, {
            shade: [0.1, '#fff'] //0.1透明度的白色背景
        });
        $.ajax({
            url: url,
            type: 'PUT',
            timeout: 15000,
            error: function (e) {
                setTimeout(function () {
                    layer.close(index_load)
                },200)
                layer.open({
                    type: 2, anim: 6, title: e.statusText, area: ['700px', '450px'],
                    fixed: false, maxmin: true, shadeClose: true, content: url
                });
            },
            success: function (e) {
                setTimeout(function () {
                    layer.close(index_load)
                },200)
                if (e.resp_code == "0401") {
                    layer.msg('登陆失效', {icon: 2}, function () {
                        window.location.href = error_url
                    });
                } else if (e.resp_code != '0000') {
                    layer.msg(e.msg, {icon: 2, anim: 6})
                } else {
                    // ut(e.ut)
                    // um(e.um)
                    var templet = '';
                    if (e.data.templet) {
                        templet = e.data.templet;
                    }
                    if (e.data.tpl) {
                        $('#layui-body').html(templet + '<div class="layui-main">' + e.data.tpl + '</div>')
                    } else {
                        $('#layui-body').html(templet + '<div class="layui-main">' + e.data + '</div>')
                    }
                }
            }
        });
    }
})


element.on('tabDelete(admin-tab)', function (data) {
    if ($('#admin-tab-title li').length < 1) {
        var top = $('#menu-top').find('.layui-nav-item:first');
        var m = top.attr('data-menu');
        top.click();
        if (m) {
            $('#menu-left').find('[data-menu-node=' + m + ']:first').find('a:first').click();
            $('#menu-left').find('[data-menu-node=' + m + ']:first').find('a:first').click();
        }
    }
});

form.on('submit(*)', function (data) {
    var url = $(data.form).attr('action');
    $.post(url, data.field, function (e) {
        if (e.resp_code == '0000') {
            layer.msg(e.msg, {icon: 1})
        } else {
            layer.msg(e.msg, {icon: 2, anim: 6})
        }
    })
    return false; //阻止表单跳转。如果需要表单跳转，去掉这段即可。
});
form.on('submit(add)', function (data) {

    console.log(data.form)
    var url = $(data.form).attr('action');
    $.xpatch(url, data.field, function (e) {
        if (e.resp_code == '0000') {
            layer.msg(e.msg, {icon: 1})
        } else {
            layer.msg(e.msg, {icon: 2, anim: 6})
        }
    })
    return false;
});
form.on('submit(delete)', function (data) {
    var url = $(data.form).attr('action');
    $.xdelete(url, data.field, function (e) {
        if (e.resp_code == '0000') {
            layer.msg(e.msg, {icon: 1})
        } else {
            layer.msg(e.msg, {icon: 2, anim: 6})
        }
    })
    return false;
});


$.xget = function (url, callback, retry) {
    if (retry === undefined) retry = 1;
    $.ajax({
        type: 'GET',
        url: url,
        dataType: 'json',
        timeout: 6000000,
        success: function (e) {
            if (!e) return layer.msg('Server Response Empty!', {icon: 2, anim: 6});
            if (e.resp_code == "0401") {
                layer.msg('登陆失效', {icon: 2}, function () {
                    window.location.href = error_url
                });
            } else if (e.resp_code != '0000') {
                layer.msg(e.msg, {icon: 2, anim: 6})
            } else {
                return callback(e);
            }
            return false;
        },
        // 网络错误，重试
        error: function (xhr, type) {
            if (retry > 1) {
                $.xget(url, callback, retry - 1);
            } else {
                layer.msg(xhr.responseText, {icon: 2, anim: 6})
            }
        }
    });
};

$.xpost = function (url, postdata, callback, progress_callback) {
    if ($.isFunction(postdata)) {
        callback = postdata;
        postdata = null;
    }

    $.ajax({
        type: 'POST',
        url: url,
        data: postdata,
        dataType: 'json',
        timeout: 6000000,
        progress: function (e) {
            if (e.lengthComputable) {
                if (progress_callback) progress_callback(e.loaded / e.total * 100);
            }
        },
        success: function (e) {
            if (!e) return layer.msg('Server Response Empty!', {icon: 2, anim: 6});
            if (e.resp_code == "0401") {
                layer.msg('登陆失效', {icon: 2}, function () {
                    window.location.href = error_url
                });
            } else if (e.resp_code == '0000') {
                return callback(e);
            } else {
                layer.msg(e.msg, {icon: 2, anim: 6})
                return callback(e);
            }
        },
        error: function (xhr, type) {
            layer.msg(xhr.responseText, {icon: 2, anim: 6})
        }
    });
};

$.xpatch = function (url, postdata, callback, progress_callback) {
    if ($.isFunction(postdata)) {
        callback = postdata;
        postdata = null;
    }

    $.ajax({
        type: 'PATCH',
        url: url,
        data: postdata,
        dataType: 'json',
        timeout: 6000000,
        progress: function (e) {
            if (e.lengthComputable) {
                if (progress_callback) progress_callback(e.loaded / e.total * 100);
            }
        },
        success: function (e) {
            if (!e) return layer.msg('Server Response Empty!', {icon: 2, anim: 6});
            if (e.resp_code == "0401") {
                layer.msg('登陆失效', {icon: 2}, function () {
                    window.location.href = error_url
                });
            } else if (e.resp_code == '0000') {
                return callback(e);
            } else {
                layer.msg(e.msg, {icon: 2, anim: 6})
                return callback(e);
            }
        },
        error: function (xhr, type) {
            layer.msg(xhr.responseText, {icon: 2, anim: 6})
        }
    });
};

$.xdelete = function (url, postdata, callback, progress_callback) {
    if ($.isFunction(postdata)) {
        callback = postdata;
        postdata = null;
    }

    $.ajax({
        type: 'DELETE',
        url: url,
        data: postdata,
        dataType: 'json',
        timeout: 6000000,
        progress: function (e) {
            if (e.lengthComputable) {
                if (progress_callback) progress_callback(e.loaded / e.total * 100);
            }
        },
        success: function (e) {
            if (!e) return layer.msg('Server Response Empty!', {icon: 2, anim: 6});
            if (e.resp_code == "0401") {
                layer.msg('登陆失效', {icon: 2}, function () {
                    window.location.href = error_url
                });
            } else if (e.resp_code == '0000') {
                return callback(e);
            } else {
                layer.msg(e.msg, {icon: 2, anim: 6})
                return callback(e);
            }
        },
        error: function (xhr, type) {
            layer.msg(xhr.responseText, {icon: 2, anim: 6})
        }
    });
};

$.cookie = function(name, value, time, path) {
    if(typeof value != 'undefined') {
        if (value === null) {
            var value = '';
            var time = -1;
        }
        if(typeof time != 'undefined') {
            date = new Date();
            date.setTime(date.getTime() + (time * 1000));
            var time = '; expires=' + date.toUTCString();
        } else {
            var time = '';
        }
        var path = path ? '; path=' + path : '';
        document.cookie = name + '=' + encodeURIComponent(value) + time + path;
    } else {
        var v = '';
        if(document.cookie && document.cookie != '') {
            var cookies = document.cookie.split(';');
            for(var i = 0; i < cookies.length; i++) {
                var cookie = $.trim(cookies[i]);
                if(cookie.substring(0, name.length + 1) == (name + '=')) {
                    v = decodeURIComponent(cookie.substring(name.length + 1)) + '';
                    break;
                }
            }
        }
        return v;
    }
};
