<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APK信息管理</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            width: 100%;
            max-width: 560px;
            transition: transform 0.3s ease;
        }

        .container:hover {
            transform: translateY(-2px);
        }

        .header {
            text-align: center;
            margin-bottom: 32px;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
            letter-spacing: -0.5px;
        }

        .header .subtitle {
            color: #718096;
            font-size: 14px;
            margin-top: 8px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 6px;
        }

        .form-group label .required {
            color: #fc8181;
            margin-left: 2px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f7fafc;
            color: #2d3748;
            outline: none;
        }

        .form-group input:focus {
            border-color: #667eea;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
        }

        .form-group input::placeholder {
            color: #a0aec0;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
            margin-top: 8px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .message {
            margin-top: 20px;
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            display: none;
            animation: slideIn 0.3s ease;
        }

        .message.success {
            display: block;
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .message.error {
            display: block;
            background: #fed7d7;
            color: #9b2c2c;
            border: 1px solid #feb2b2;
        }

        .message.info {
            display: block;
            background: #bee3f8;
            color: #2a4365;
            border: 1px solid #90cdf4;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .footer {
            text-align: center;
            margin-top: 24px;
            color: #a0aec0;
            font-size: 13px;
        }

        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s ease-in-out infinite;
            vertical-align: middle;
            margin-right: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 480px) {
            .container {
                padding: 28px 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .header h1 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 APK 信息管理</h1>
            <p class="subtitle">填写应用信息以生成或更新配置文件</p>
        </div>

        <form id="apkForm" method="POST">
            <div class="form-group">
                <label>SN 编号 <span class="required">*</span></label>
                <input type="text" name="sn" placeholder="请输入纯数字编号" pattern="[0-9]+" required>
            </div>

            <div class="form-group">
                <label>APK 下载地址 <span class="required">*</span></label>
                <input type="url" name="apkUrl" placeholder="https://example.com/app.apk" required>
            </div>

            <div class="form-group">
                <label>APK 名称 <span class="required">*</span></label>
                <input type="text" name="apkName" placeholder="例如：微信" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>包名 <span class="required">*</span></label>
                    <input type="text" name="packageName" placeholder="com.example.app" required>
                </div>
                <div class="form-group">
                    <label>MD5 值 <span class="required">*</span></label>
                    <input type="text" name="md5" placeholder="32位十六进制" required>
                </div>
            </div>

            <button type="submit" class="submit-btn" id="submitBtn">✨ 提交信息</button>
        </form>

        <div id="message" class="message"></div>
        <div class="footer">有问题请联系 无处不在的草方块</div>
    </div>

    <?php
    require_once 'iotUnionApi.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sn = isset($_POST['sn']) ? trim($_POST['sn']) : '';
        $apkUrl = isset($_POST['apkUrl']) ? trim($_POST['apkUrl']) : '';
        $apkName = isset($_POST['apkName']) ? trim($_POST['apkName']) : '';
        $packageName = isset($_POST['packageName']) ? trim($_POST['packageName']) : '';
        $md5 = isset($_POST['md5']) ? trim($_POST['md5']) : '';

        $errors = [];
        if (empty($sn)) $errors[] = 'SN编号不能为空';
        if (!preg_match('/^[0-9]+$/', $sn)) $errors[] = 'SN编号只能为纯数字';
        if (empty($apkUrl)) $errors[] = 'APK下载地址不能为空';
        if (empty($apkName)) $errors[] = 'APK名称不能为空';
        if (empty($packageName)) $errors[] = '包名不能为空';
        if (empty($md5)) $errors[] = 'MD5值不能为空';

        if (empty($errors)) {
            $snDir = __DIR__ . '/sn';
            if (!is_dir($snDir)) {
                mkdir($snDir, 0777, true);
            }

            $filePath = $snDir . '/' . $sn . '.json';
            $data = [
                'apkUrl' => $apkUrl,
                'apkName' => $apkName,
                'packageName' => $packageName,
                'md5' => $md5
            ];

            if (file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
               	$iotunion_token = "eyJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjgyNDU1NDQxLCJqdGkiOiI1Y2QyMWM0Y2IxNTI0YzRlODRjMjM1ZmFmYmY4ZWY3NyIsImlhdCI6MTc3MTkyNjE1Niwic3ViIjoiODI0NTU0NDEifQ.tCvrn-NHvb0FJtNTWYwrmFVLnxPbmBJ7F4bETGUYX5w";
				$iotunion_secret = "8db6d110b4b949f282d24f4b6197e30b";
                
                $result = parentInstallApp($iotunion_token, $iotunion_secret, $sn, 2999);
                $response = json_decode($result, true);
                
                if ($response && isset($response['errNo']) && $response['errNo'] === 0) {
                    echo '<script>
                        document.getElementById("message").className = "message success";
                        document.getElementById("message").textContent = "✅ 数据保存成功，应用安装指令已下发！";
                        document.getElementById("submitBtn").disabled = false;
                        document.getElementById("submitBtn").innerHTML = "✨ 提交信息";
                    </script>';
                } else {
                    $errMsg = isset($response['errMsg']) ? $response['errMsg'] : '未知错误';
                    echo '<script>
                        document.getElementById("message").className = "message error";
                        document.getElementById("message").textContent = "⚠️ 数据已保存，但应用安装指令下发失败：' . addslashes($errMsg) . '";
                        document.getElementById("submitBtn").disabled = false;
                        document.getElementById("submitBtn").innerHTML = "✨ 提交信息";
                    </script>';
                }
            } else {
                echo '<script>
                    document.getElementById("message").className = "message error";
                    document.getElementById("message").textContent = "❌ 数据保存失败，请检查目录权限";
                    document.getElementById("submitBtn").disabled = false;
                    document.getElementById("submitBtn").innerHTML = "✨ 提交信息";
                </script>';
            }
        } else {
            $errorMsg = implode('、', $errors);
            echo '<script>
                document.getElementById("message").className = "message error";
                document.getElementById("message").textContent = "❌ ' . $errorMsg . '";
                document.getElementById("submitBtn").disabled = false;
                document.getElementById("submitBtn").innerHTML = "✨ 提交信息";
            </script>';
        }
    }
    ?>

    <script>
        document.getElementById('apkForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="loading-spinner"></span>提交中...';
            const msg = document.getElementById('message');
            msg.className = 'message';
            msg.textContent = '';
        });

        document.querySelector('input[name="sn"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>