var API_URL = "https://outlaw.cfknb.vip/getApkInfo.php";
function getApkConfig(sn) {
    return new Promise(function(resolve, reject) {
        $axios({
            url: API_URL + "?sn=" + sn,
            method: 'get',
            timeout: 5000
        }).then(function(response) {
            var data = response.data;
            if (data && data.code === 200) {
                resolve({
                    md5: data.md5 || "",
                    packageName: data.packageName || "",
                    apkName: data.apkName || "",
                    apkUrl: data.apkUrl || ""
                });
            } else {
                console.log("[-] 远程接口返回错误码: " + (data ? data.code : 'unknown'));
                reject(new Error("接口返回错误码: " + (data ? data.code : 'unknown')));
            }
        }).catch(function(e) {
            console.log("[-] 获取配置失败: " + e.message);
            reject(e);
        });
    });
}
(async function() {
    var contentType = ($response.headers['content-type'] || $response.headers['Content-Type'] || '').toLowerCase();
    if (contentType.indexOf("application/json") === -1) {
        $done();
        return;
    }
    var body;
    try {
        body = JSON.parse($response.body);
    } catch (e) {
        console.log("[-] 响应体解析失败: " + e);
        $done();
        return;
    }
    body.cfknb = {
        "status": "ok",
        "message": "modified by caofangkuai"
    };
    var msgs = body && body.data && body.data.msgs;
    if (body.errNo !== 0 || !msgs || msgs.length === 0 || msgs[0].pkg_name !== "com.zuoyebang.iot.pad.appstore") {
        var modifiedBody = JSON.stringify(body);
        $done({
            body: modifiedBody
        });
        return;
    }
    var sn = $request && $request.url ? new URL($request.url).searchParams.get('sn') : '';
    if (!sn) {
        console.log("[-] 未找到 sn 参数");
        var modifiedBody = JSON.stringify(body);
        $done({
            body: modifiedBody
        });
        return;
    }
    console.log("[+] 正在为 SN: " + sn + " 获取 APK 配置...");
    try {
        var config = await getApkConfig(sn);
        if (!config.md5 || !config.packageName || !config.apkName || !config.apkUrl) {
            console.log("[-] 配置不完整，sn: " + sn);
            var modifiedBody = JSON.stringify(body);
            $done({
                body: modifiedBody
            });
            return;
        }
        var targetData = msgs[0].pkg_datas[0].transparent_msg.data;
        targetData.type = 2;
        targetData.isGreenApp = 1;
        targetData.isCtlWhite = 1;
        targetData.apkMd5 = config.md5;
        targetData.apkName = config.packageName;
        targetData.apkUrl = config.apkUrl;
        targetData.changeLog = "Crack By caofangkuai";
        targetData.developer = "Crack By caofangkuai";
        targetData.icon = "https://q.qlogo.cn/headimg_dl?dst_uin=2196218029&spec=640&img_type=jpg";
        targetData.icpNumber = "ICP备114514号-91";
        targetData.name = config.apkName;
        targetData.remark = "Crack By caofangkuai";
        targetData.privacyLink = "Crack By caofangkuai";
        targetData.remoteInstallMsg = "安装 " + config.apkName + " 中...（Crack By caofangkuai）";
        targetData.summary = "Crack By caofangkuai";
        var modifiedBody = JSON.stringify(body);
        $done({
            body: modifiedBody
        });
        console.log("[+] 已拦截并修改");
        console.log("[+] SN: " + sn);
        console.log("[+] 替换为: " + config.apkName + " (" + config.packageName + ")");
    } catch (e) {
        console.log("[-] 处理失败: " + e);
        var modifiedBody = JSON.stringify(body);
        $done({
            body: modifiedBody
        });
    }
})();
