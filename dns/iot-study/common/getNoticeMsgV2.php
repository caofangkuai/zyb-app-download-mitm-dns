<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

// ============ 配置区域 ============
// 访问日志开关：true-开启，false-关闭
define('ENABLE_ACCESS_LOG', true);
// 日志文件路径（当前目录）
define('LOG_FILE_PATH', __DIR__ . '/access_log.txt');
// =================================

// 配置API URL
define('API_URL', 'https://outlaw.cfknb.vip/getApkInfo.php');

/**
 * 写入访问日志
 * @param string $requestUrl 请求的URL
 * @param string $response 返回结果
 * @param array $additionalInfo 额外信息
 */
function writeAccessLog($requestUrl, $response, $additionalInfo = []) {
    if (!ENABLE_ACCESS_LOG) {
        return;
    }
    
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'request_url' => $requestUrl,
        'response' => $response,
        'additional_info' => $additionalInfo
    ];
    
    // 格式化日志行
    $logLine = json_encode($logData, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    
    // 写入文件（追加模式）
    @file_put_contents(LOG_FILE_PATH, $logLine, FILE_APPEND | LOCK_EX);
}

/**
 * 处理OPTIONS请求（CORS预检）
 */
function handleOptionsRequest() {
    // 设置CORS响应头
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
    header('Access-Control-Max-Age: 86400'); // 预检结果缓存24小时
    header('Content-Length: 0');
    
    // 记录OPTIONS请求日志
    $originalRequestUrl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    writeAccessLog($originalRequestUrl, 'OPTIONS request handled', [
        'type' => 'options_request',
        'headers' => getallheaders()
    ]);
    
    http_response_code(200);
    exit;
}

/**
 * 获取APK配置信息
 */
function getApkConfig($sn, &$errorLog = '') {
    $url = API_URL . '?sn=' . urlencode($sn);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        $errorLog = 'CURL错误: ' . $curlError;
        return null;
    }
    
    if ($httpCode !== 200) {
        $errorLog = 'HTTP错误: ' . $httpCode;
        return null;
    }
    
    if (empty($response)) {
        $errorLog = '响应为空';
        return null;
    }
    
    $data = json_decode($response, true);
    if ($data === null) {
        $errorLog = 'JSON解析失败';
        return null;
    }
    
    if (!isset($data['code']) || $data['code'] !== 200) {
        $errorLog = '接口返回错误码: ' . ($data['code'] ?? 'unknown');
        return null;
    }
    
    return [
        'md5' => $data['md5'] ?? '',
        'packageName' => $data['packageName'] ?? '',
        'apkName' => $data['apkName'] ?? '',
        'apkUrl' => $data['apkUrl'] ?? ''
    ];
}

/**
 * 处理请求
 */
function handleRequest(&$errorLog = '') {
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    
    // 处理OPTIONS请求
    if ($requestMethod === 'OPTIONS') {
        handleOptionsRequest();
        return null;
    }
    
    // 只允许POST请求
    if ($requestMethod !== 'POST') {
        http_response_code(405);
        return json_encode([
            'error' => 'Method Not Allowed',
            'message' => 'Only POST requests are accepted'
        ], JSON_UNESCAPED_UNICODE);
    }
    
    $targetUrl = 'http://iot-admin.zuoyebang.com' . $_SERVER['REQUEST_URI'];
    $postData = file_get_contents("php://input");
    
    // 检查是否有POST数据
    if (empty($postData)) {
        http_response_code(400);
        return json_encode([
            'error' => 'Bad Request',
            'message' => 'POST data is required'
        ], JSON_UNESCAPED_UNICODE);
    }
    
    // 判断数据格式
    $isJson = json_decode($postData) !== null;
    
    $ch = curl_init($targetUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    // 设置请求头
    $headers = [];
    if ($isJson) {
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Content-Length: ' . strlen($postData);
    } else {
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    }
    
    // 转发原始请求的Headers
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers[] = 'Authorization: ' . $_SERVER['HTTP_AUTHORIZATION'];
    }
    if (isset($_SERVER['HTTP_ACCEPT'])) {
        $headers[] = 'Accept: ' . $_SERVER['HTTP_ACCEPT'];
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // 执行请求
    $response = curl_exec($ch);
    
    // 错误处理
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        http_response_code(500);
        return json_encode([
            'error' => 'CURL Error',
            'message' => $error
        ], JSON_UNESCAPED_UNICODE);
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // 检查HTTP状态码
    if ($httpCode >= 400) {
        http_response_code($httpCode);
        return json_encode([
            'error' => 'HTTP Error',
            'code' => $httpCode,
            'response' => $response
        ], JSON_UNESCAPED_UNICODE);
    }
    
    return $response;
}

// ============ 主程序入口 ============

// 处理OPTIONS请求（在记录日志之前）
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    handleOptionsRequest();
    // handleOptionsRequest 会调用 exit，所以这里不会继续执行
}

// 记录原始请求URL（用于日志）
$originalRequestUrl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// 执行请求处理
$response = handleRequest();

// 如果 handleRequest 返回 null（OPTIONS请求已处理），则退出
if ($response === null) {
    exit;
}

// 解析响应数据
$data = json_decode($response, true);

// 如果响应不是JSON，直接返回并记录日志
if ($data === null) {
    // 记录日志
    writeAccessLog($originalRequestUrl, $response, ['type' => 'non_json_response']);
    echo $response;
    exit;
}

// 初始化错误日志数组
$errorLogs = [];

// 添加自定义标记
$data['cfknb'] = [
    'status' => 'ok',
    'message' => 'modified by caofangkuai'
];

// 检查是否需要处理特定的包
$msgs = &$data['data']['msgs'];
$shouldProcess = false;

if (isset($data['errNo']) && $data['errNo'] === 0 && !empty($msgs)) {
    $pkgName = isset($msgs[0]['pkg_name']) ? $msgs[0]['pkg_name'] : '';
    if ($pkgName === 'com.zuoyebang.iot.pad.appstore') {
        $shouldProcess = true;
    }
}

// 记录处理状态
$processStatus = '跳过处理';
$modificationDetails = [];

// 如果需要处理
if ($shouldProcess) {
    // 获取SN参数
    $sn = isset($_GET['sn']) ? $_GET['sn'] : '';
    
    if (empty($sn)) {
        $errorLogs[] = '未找到 sn 参数';
        $processStatus = '未找到SN参数';
    } else {
        // 获取APK配置
        $configError = '';
        $config = getApkConfig($sn, $configError);
        
        if ($configError) {
            $errorLogs[] = $configError;
            $processStatus = '获取配置失败';
        }
        
        // 检查配置完整性
        if ($config !== null && 
            !empty($config['md5']) && 
            !empty($config['packageName']) && 
            !empty($config['apkName']) && 
            !empty($config['apkUrl'])) {
            
            // 修改目标数据
            if (isset($msgs[0]['pkg_datas'][0]['transparent_msg']['data'])) {
                $targetData = &$msgs[0]['pkg_datas'][0]['transparent_msg']['data'];
                $targetData['type'] = 2;
                $targetData['isGreenApp'] = 1;
                $targetData['isCtlWhite'] = 1;
                $targetData['apkMd5'] = $config['md5'];
                $targetData['apkName'] = $config['packageName'];
                $targetData['apkUrl'] = $config['apkUrl'];
                $targetData['changeLog'] = 'Crack By caofangkuai';
                $targetData['developer'] = 'Crack By caofangkuai';
                $targetData['icon'] = 'https://q.qlogo.cn/headimg_dl?dst_uin=2196218029&spec=640&img_type=jpg';
                $targetData['icpNumber'] = 'ICP备114514号-91';
                $targetData['name'] = $config['apkName'];
                $targetData['remark'] = 'Crack By caofangkuai';
                $targetData['privacyLink'] = 'Crack By caofangkuai';
                $targetData['remoteInstallMsg'] = '安装 ' . $config['apkName'] . ' 中...（Crack By caofangkuai）';
                $targetData['summary'] = 'Crack By caofangkuai';

                $errorLogs[] = '成功拦截并修改';
                $errorLogs[] = 'SN: ' . $sn;
                $errorLogs[] = '替换为: ' . $config['apkName'] . ' (' . $config['packageName'] . ')';
                
                $processStatus = '成功修改';
                $modificationDetails = [
                    'sn' => $sn,
                    'apk_name' => $config['apkName'],
                    'package_name' => $config['packageName'],
                    'apk_url' => $config['apkUrl']
                ];
            } else {
                $errorLogs[] = '目标数据结构不存在';
                $processStatus = '数据结构异常';
            }
        } elseif ($config !== null) {
            $missing = [];
            if (empty($config['md5'])) $missing[] = 'md5';
            if (empty($config['packageName'])) $missing[] = 'packageName';
            if (empty($config['apkName'])) $missing[] = 'apkName';
            if (empty($config['apkUrl'])) $missing[] = 'apkUrl';
            $errorLogs[] = '配置不完整，缺少: ' . implode(', ', $missing);
            $processStatus = '配置不完整';
        }
    }
} else {
    $errorLogs[] = '跳过处理（不匹配的条件）';
    if (!isset($data['errNo']) || $data['errNo'] !== 0) {
        $errorLogs[] = 'errNo: ' . ($data['errNo'] ?? '未设置');
    }
    if (empty($msgs)) {
        $errorLogs[] = 'msgs为空';
    } elseif (!empty($msgs)) {
        $pkgName = isset($msgs[0]['pkg_name']) ? $msgs[0]['pkg_name'] : '未设置';
        $errorLogs[] = '包名: ' . $pkgName . ' (需要: com.zuoyebang.iot.pad.appstore)';
    }
    $processStatus = '条件不匹配';
}

// 添加错误日志到返回结果
$data['error_log'] = $errorLogs;

// 将最终结果转为JSON
$finalResponse = json_encode($data, JSON_UNESCAPED_UNICODE);

// 写入访问日志
writeAccessLog($originalRequestUrl, $finalResponse, [
    'process_status' => $processStatus,
    'modification' => $modificationDetails
]);

// 输出修改后的JSON
echo $finalResponse;
?>
