var ws = {};
$(document).ready(function () {
    if(!webim.server){
        return;
    }
	if (window.WebSocket || window.MozWebSocket)
    {
        ws = new ReconnectingWebSocket(webim.server,null,{maxReconnectAttempts:10,timeoutInterval:3000});
    }
    //使用flash websocket
    else if (webim.flash_websocket)
    {
        WEB_SOCKET_SWF_LOCATION = "/static/websocket/WebSocketMain.swf";
        $.getScript("/static/websocket/swfobject.js", function () {
                ws = new WebSocket(webim.server);
        });
    }

    listenEvent();


//监听在线状态的切换事件
    layim.on('online', function(status){
        layer.msg(status);

    });

    //监听签名修改
    layim.on('sign', function(value){

    });

    //监听自定义工具栏点击，以添加代码为例
    layim.on('tool(code)', function(insert){
        layer.prompt({
            title: '插入代码 - 工具栏扩展示例'
            ,formType: 2
            ,shade: 0
        }, function(text, index){
            layer.close(index);
            insert('[pre class=layui-code]' + text + '[/pre]'); //将内容插入到编辑器
        });
    });

//监听发送消息
    layim.on('sendMessage', function(data){
        var To = data.to;
        if(To.type === 'friend'){
            //layim.setChatStatus('<span style="color:#FF5722;">对方正在输入。。。</span>');
            sendMsg('customerMessage',data);
        }
    });
//监听查看群员
//     layim.on('members', function(data){
//         console.log(data);
//     });

//监听聊天窗口的切换
//     layim.on('chatChange', function(res){
//         var type = res.data.type;
//         console.log(res.data.id)
//         if(type === 'friend'){
//             //模拟标注好友状态
//             //layim.setChatStatus('<span style="color:#FF5722;">在线</span>');
//         } else if(type === 'group'){
//             //模拟系统消息
//             layim.getMessage({
//                 system: true
//                 ,id: res.data.id
//                 ,type: "group"
//                 ,content: '模拟群员'+(Math.random()*100|0) + '加入群聊'
//             });
//         }
//     });


//面板外的操作
//     var active = {
//         chat: function(){
//             //自定义会话
//             layim.chat({
//                 name: '小闲'
//                 ,type: 'friend'
//                 ,avatar: '//tva3.sinaimg.cn/crop.0.0.180.180.180/7f5f6861jw1e8qgp5bmzyj2050050aa8.jpg'
//                 ,id: 1008612
//             });
//             layer.msg('也就是说，此人可以不在好友面板里');
//         }
//         ,message: function(){
//             //制造好友消息
//             layim.getMessage({
//                 username: "贤心"
//                 ,avatar: "//tp1.sinaimg.cn/1571889140/180/40030060651/1"
//                 ,id: "100001"
//                 ,type: "friend"
//                 ,content: "嗨，你好！欢迎体验LayIM。演示标记："+ new Date().getTime()
//                 ,timestamp: new Date().getTime()
//             });
//         }
//         ,messageAudio: function(){
//             //接受音频消息
//             layim.getMessage({
//                 username: "林心如"
//                 ,avatar: "//tp3.sinaimg.cn/1223762662/180/5741707953/0"
//                 ,id: "76543"
//                 ,type: "friend"
//                 ,content: "audio[http://gddx.sc.chinaz.com/Files/DownLoad/sound1/201510/6473.mp3]"
//                 ,timestamp: new Date().getTime()
//             });
//         }
//         ,messageVideo: function(){
//             //接受视频消息
//             layim.getMessage({
//                 username: "林心如"
//                 ,avatar: "//tp3.sinaimg.cn/1223762662/180/5741707953/0"
//                 ,id: "76543"
//                 ,type: "friend"
//                 ,content: "video[http://www.w3school.com.cn//i/movie.ogg]"
//                 ,timestamp: new Date().getTime()
//             });
//         }
//         ,messageTemp: function(){
//             //接受临时会话消息
//             layim.getMessage({
//                 username: "小酱"
//                 ,avatar: "//tva1.sinaimg.cn/crop.7.0.736.736.50/bd986d61jw8f5x8bqtp00j20ku0kgabx.jpg"
//                 ,id: "198909151014"
//                 ,type: "friend"
//                 ,content: "临时："+ new Date().getTime()
//             });
//         }
//         ,add: function(){
//             //实际使用时数据由动态获得
//             layim.add({
//                 type: 'friend'
//                 ,username: '麻花疼'
//                 ,avatar: '//tva1.sinaimg.cn/crop.0.0.720.720.180/005JKVuPjw8ers4osyzhaj30k00k075e.jpg'
//                 ,submit: function(group, remark, index){
//                     layer.msg('好友申请已发送，请等待对方确认', {
//                         icon: 1
//                         ,shade: 0.5
//                     }, function(){
//                         layer.close(index);
//                     });
//
//                     //通知对方
//                     /*
//                     $.post('/im-applyFriend/', {
//                       uid: info.uid
//                       ,from_group: group
//                       ,remark: remark
//                     }, function(res){
//                       if(res.status != 0){
//                         return layer.msg(res.msg);
//                       }
//                       layer.msg('好友申请已发送，请等待对方确认', {
//                         icon: 1
//                         ,shade: 0.5
//                       }, function(){
//                         layer.close(index);
//                       });
//                     });
//                     */
//                 }
//             });
//         }
//         ,addqun: function(){
//             layim.add({
//                 type: 'group'
//                 ,username: 'LayIM会员群'
//                 ,avatar: '//tva2.sinaimg.cn/crop.0.0.180.180.50/6ddfa27bjw1e8qgp5bmzyj2050050aa8.jpg'
//                 ,submit: function(group, remark, index){
//                     layer.msg('申请已发送，请等待管理员确认', {
//                         icon: 1
//                         ,shade: 0.5
//                     }, function(){
//                         layer.close(index);
//                     });
//
//                     //通知对方
//                     /*
//                     $.post('/im-applyGroup/', {
//                       uid: info.uid
//                       ,from_group: group
//                       ,remark: remark
//                     }, function(res){
//
//                     });
//                     */
//                 }
//             });
//         }
//         ,addFriend: function(){
//             var user = {
//                 type: 'friend'
//                 ,id: 1234560
//                 ,username: '李彦宏' //好友昵称，若申请加群，参数为：groupname
//                 ,avatar: '//tva4.sinaimg.cn/crop.0.0.996.996.180/8b2b4e23jw8f14vkwwrmjj20ro0rpjsq.jpg' //头像
//                 ,sign: '全球最大的中文搜索引擎'
//             }
//             layim.setFriendGroup({
//                 type: user.type
//                 ,username: user.username
//                 ,avatar: user.avatar
//                 ,group: layim.cache().friend //获取好友列表数据
//                 ,submit: function(group, index){
//                     //一般在此执行Ajax和WS，以通知对方已经同意申请
//                     //……
//
//                     //同意后，将好友追加到主面板
//                     layim.addList({
//                         type: user.type
//                         ,username: user.username
//                         ,avatar: user.avatar
//                         ,groupid: group //所在的分组id
//                         ,id: user.id //好友ID
//                         ,sign: user.sign //好友签名
//                     });
//
//                     layer.close(index);
//                 }
//             });
//         }
//         ,addGroup: function(){
//             layer.msg('已成功把[Angular开发]添加到群组里', {
//                 icon: 1
//             });
//             //增加一个群组
//             layim.addList({
//                 type: 'group'
//                 ,avatar: "//tva3.sinaimg.cn/crop.64.106.361.361.50/7181dbb3jw8evfbtem8edj20ci0dpq3a.jpg"
//                 ,groupname: 'Angular开发'
//                 ,id: "12333333"
//                 ,members: 0
//             });
//         }
//         ,removeFriend: function(){
//             layer.msg('已成功删除[凤姐]', {
//                 icon: 1
//             });
//             //删除一个好友
//             layim.removeList({
//                 id: 121286
//                 ,type: 'friend'
//             });
//         }
//         ,removeGroup: function(){
//             layer.msg('已成功删除[前端群]', {
//                 icon: 1
//             });
//             //删除一个群组
//             layim.removeList({
//                 id: 101
//                 ,type: 'group'
//             });
//         }
//         //置灰离线好友
//         ,setGray: function(){
//             layim.setFriendStatus(168168, 'offline');
//
//             layer.msg('已成功将好友[马小云]置灰', {
//                 icon: 1
//             });
//         }
//         //取消好友置灰
//         ,unGray: function(){
//             layim.setFriendStatus(168168, 'online');
//
//             layer.msg('成功取消好友[马小云]置灰状态', {
//                 icon: 1
//             });
//         }
//         //移动端版本
//         ,mobile: function(){
//             var device = layui.device();
//             var mobileHome = '/layim/demo/mobile.html';
//             if(device.android || device.ios){
//                 return location.href = mobileHome;
//             }
//             var index = layer.open({
//                 type: 2
//                 ,title: '移动版演示 （或手机扫右侧二维码预览）'
//                 ,content: mobileHome
//                 ,area: ['375px', '667px']
//                 ,shadeClose: true
//                 ,shade: 0.8
//                 ,end: function(){
//                     layer.close(index + 2);
//                 }
//             });
//             layer.photos({
//                 photos: {
//                     "data": [{
//                         "src": "http://cdn.layui.com/upload/2016_12/168_1481056358469_50288.png",
//                     }]
//                 }
//                 ,anim: 0
//                 ,shade: false
//                 ,success: function(layero){
//                     layero.css('margin-left', '350px');
//                 }
//             });
//         }
//     };

});

function listenEvent() {
    /**
     * 连接建立时触发
     */
    var uid_arr=[];
    ws.onopen = function (e) {
        $.notify("连接连接成功。", "success");
        sendMsg('ping','login');
        setInterval(function () {
            sendMsg('ping','login');
        },30000);
    };

    //有消息到来时触发
    ws.onmessage = function (e) {
        var message = JSON.parse(e.data);
        console.log(message.data);
        var code = message.resp_code;
        if (code == '0000') {
            if(message.msg){
                switch (message.cmd) {
                    case "order":
                        layer.open({
                            type: 1
                            ,offset: "rb" //具体配置参考：http://www.layui.com/doc/modules/layer.html#offset
                            ,id: 'order_tips' //防止重复弹出
                            ,content: '<div style="padding: 20px 100px;">'+ message.msg +'</div>'
                            ,btn: '快速查看'
                            ,title:"新小票待审核"
                            , time: 120000

                            //,btnAlign: 'c' //按钮居中
                            ,shade: 0 //不显示遮罩
                            ,yes: function(){
                                $("[data-open='tab-store-order-index']").trigger("click");
                                layer.closeAll();
                            }
                        });
                        return;
                        break;
                    case "team":
                        layer.open({
                            type: 1
                            ,offset: "rb" //具体配置参考：http://www.layui.com/doc/modules/layer.html#offset
                            ,id: 'order_tips' //防止重复弹出
                            ,content: '<div style="padding: 20px 100px;">'+ message.msg +'</div>'
                            ,title:"新预约申请"
                            ,btn: '快速查看'
                            , time: 120000

                            //,btnAlign: 'c' //按钮居中
                            ,shade: 0 //不显示遮罩
                            ,yes: function(){
                                $("[data-open='tab-store-subscribe-index']").trigger("click");
                                layer.closeAll();
                            }
                        });
                        return;
                        break;
                    case "tx":
                        layer.open({
                            type: 1
                            ,offset: "rb" //具体配置参考：http://www.layui.com/doc/modules/layer.html#offset
                            ,id: 'order_tips' //防止重复弹出
                            ,content: '<div style="padding: 20px 100px;">'+ message.msg +'</div>'
                            ,title:"提现申请"
                            ,btn: '快速查看'
                            , time: 120000

                            //,btnAlign: 'c' //按钮居中
                            ,shade: 0 //不显示遮罩
                            ,yes: function(){
                                $("[data-open='tab-store-tx-index']").trigger("click");
                                layer.closeAll();
                            }
                        });
                        return;
                        break;
                }
                $.notify(message.msg, "success");
            }else{
                switch (message.cmd) {
                    case "chatMessage":

                        if(!uid_arr[message.data.id]){
                            uid_arr[message.data.id]=1;
                            layim.addList({
                                type: 'friend'
                                ,username: message.data.username
                                ,avatar: message.data.avatar
                                ,groupid: 10000
                                ,id: message.data.id
                                ,sign: 'IP:255.255.255.255'
                            });
                        }

                        layim.getMessage(message.data);
                        break;
                }
            }
        }else{
            if(message.msg){
                $.notify(message.msg, "error");
            }
        }
    };

    /**
     * 连接关闭事件
     */
    ws.onclose = function (e) {
        $.notify("连接已断开，正在重新连接，请稍候。。。", "error");
    };

    /**
     * 异常事件
     */
    ws.onerror = function (e) {
        $.notify( e.data, "error");
        //console.log("onerror: " + e.data);
    };
}

function sendMsg(url,content) {
    var msg = {};
        if (typeof content == "string") {
            content = content.replace(" ", "&nbsp;");
        }
        if (!content) {
            return false;
        }
        msg.cmd = url;
        msg.data = content;
        ws.send(JSON.stringify(msg));
}