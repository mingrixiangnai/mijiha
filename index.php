<?php
/**
 * 
 * 
 * php区域开始
 * 
 * 
 */
session_start();

// 设置默认时区为东八区
date_default_timezone_set('Asia/Shanghai');

// 数据库配置
$host = 'localhost';
$dbname = 'admin';
$username = 'admin';
$password = 'admin';

// 创建数据库连接
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 设置数据库时区为东八区
    $pdo->exec("SET time_zone = '+08:00';");
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

// 处理AJAX进度查询
if (isset($_GET['action']) && $_GET['action'] == 'get_batch_progress') {
    if (isset($_SESSION['batch_progress'])) {
        header('Content-Type: application/json');
        echo json_encode($_SESSION['batch_progress']);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'idle']);
        exit();
    }
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 处理单个视频添加
    if (isset($_POST['bv_id'])) {
        $bv_id = trim($_POST['bv_id']);
        
        // 验证BV号格式
        if (preg_match('/^BV[a-zA-Z0-9]{10}$/', $bv_id)) {
            // 获取视频标签api
            $tag_api_url = "https://api.bilibili.com/x/tag/archive/tags?bvid=" . $bv_id;
            $tag_ch = curl_init();
            curl_setopt($tag_ch, CURLOPT_URL, $tag_api_url);
            curl_setopt($tag_ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($tag_ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            $tag_response = curl_exec($tag_ch);
            curl_close($tag_ch);
            
            $tag_data = json_decode($tag_response, true);
            
            // 检查标签是否包含需要的字符
            $has_valid_tag = false;
            if ($tag_data && $tag_data['code'] === 0 && !empty($tag_data['data'])) {
                foreach ($tag_data['data'] as $tag) {
                    $tag_name = $tag['tag_name'];
                    if (strpos($tag_name, '哈基米') !== false || strpos($tag_name, '曼波') !== false || strpos($tag_name, '私人音乐') !== false || strpos($tag_name, '赛马娘') !== false || strpos($tag_name, '哈牛魔') !== false || strpos($tag_name, '叮咚鸡') !== false || strpos($tag_name, '活全家') !== false || strpos($tag_name, '胖宝宝') !== false || strpos($tag_name, '小白手套') !== false || strpos($tag_name, '诗歌剧') !== false || strpos($tag_name, '踩踩背') !== false || strpos($tag_name, '东海帝王') !== false || strpos($tag_name, '猫') !== false || strpos($tag_name, '米基哈') !== false || strpos($tag_name, '基米哈') !== false || strpos($tag_name, '大狗叫') !== false || strpos($tag_name, '圆头') !== false || strpos($tag_name, '喵星人') !== false || strpos($tag_name, '爱猫tv') !== false || strpos($tag_name, '爱猫') !== false || strpos($tag_name, '踩背') !== false || strpos($tag_name, '爱猫fm') !== false || strpos($tag_name, '哈基米fm') !== false || strpos($tag_name, '哈吉米') !== false) {
                            $has_valid_tag = true;
                            break;
                    }
                }
            }
            
            if (!$has_valid_tag) {
                $_SESSION['error'] = "该视频标签不包含'哈基米、曼波、私人音乐、赛马娘、哈牛魔、叮咚鸡、活全家、胖宝宝、小白手套、诗歌剧、踩踩背、东海帝王、猫、米基哈、基米哈、耄耋、猫爹、猫咪、哈气、大狗叫、圆头、喵星人、爱猫tv、爱猫、踩背、爱猫fm、哈基米fm、哈吉米'无法添加！";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
            
            // 检查是否已存在该BV号
            try {
                $checkStmt = $pdo->prepare("SELECT id FROM videos WHERE bv_id = ?");
                $checkStmt->execute([$bv_id]);
                
                if ($checkStmt->fetch()) {
                    $_SESSION['error'] = "该视频已存在！";
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "数据库查询失败: " . $e->getMessage();
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
            
            // 获取视频信息api
            $api_url = "https://api.bilibili.com/x/web-interface/view?bvid=" . $bv_id;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            $response = curl_exec($ch);
            curl_close($ch);
            
            $data = json_decode($response, true);
            
            if ($data && $data['code'] === 0) {
                $title = $data['data']['title'];
                $cover_url = str_replace('http://', 'https://', $data['data']['pic']);
                
                // 获取当前时间（东八区）
                $current_time = date('Y-m-d H:i:s');
                
                // 插入数据库
                try {
                    $stmt = $pdo->prepare("INSERT INTO videos (bv_id, title, cover_url, created_at) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$bv_id, $title, $cover_url, $current_time]);
                    
                    // 设置成功消息并重定向
                    $_SESSION['success'] = "视频添加成功，建议刷新网页";
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } catch (PDOException $e) {
                    // 捕获唯一约束错误
                    if ($e->getCode() == 23000) {
                        $_SESSION['error'] = "该视频已存在！";
                    } else {
                        $_SESSION['error'] = "数据库错误: " . $e->getMessage();
                    }
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                }
            } else {
                $_SESSION['error'] = "无法获取视频信息，请检查BV号是否正确";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
        } else {
            $_SESSION['error'] = "无效的BV号格式，请以BV开头并包含10个字符（如：BV1gRTqzdERJ）";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
    
    // 处理收藏夹批量添加
    if (isset($_POST['favorite_id'])) {
        $favorite_id = trim($_POST['favorite_id']);
        
        // 从输入中提取收藏夹ID
        if (preg_match('/fid=(\d+)/', $favorite_id, $matches)) {
            $favorite_id = $matches[1];
        } elseif (!is_numeric($favorite_id)) {
            $_SESSION['error'] = "无效的收藏夹ID格式";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
        
        // 初始化进度信息
        $_SESSION['batch_progress'] = [
            'status' => 'processing',
            'total' => 0,
            'processed' => 0,
            'added' => 0,
            'skipped' => 0,
            'errors' => 0,
            'current_video' => '',
            'message' => '开始获取收藏夹信息...'
        ];
        
        // 获取收藏夹信息，循环处理所有页面
        $page = 1;
        $has_more = true;
        $all_media = [];
        
        while ($has_more) {
            $_SESSION['batch_progress']['message'] = "正在获取第 {$page} 页收藏夹内容...";
            session_write_close(); // 释放session，允许其他请求读取进度
            session_start();
            
            // 获取收藏夹api
            $favorite_api_url = "https://api.bilibili.com/x/v3/fav/resource/list?media_id=" . $favorite_id . "&pn=" . $page . "&ps=20";
            $favorite_ch = curl_init();
            curl_setopt($favorite_ch, CURLOPT_URL, $favorite_api_url);
            curl_setopt($favorite_ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($favorite_ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            $favorite_response = curl_exec($favorite_ch);
            curl_close($favorite_ch);
            
            $favorite_data = json_decode($favorite_response, true);
            
            if ($favorite_data && $favorite_data['code'] === 0 && !empty($favorite_data['data']['medias'])) {
                $all_media = array_merge($all_media, $favorite_data['data']['medias']);
                $has_more = $favorite_data['data']['has_more'] == 1;
                $page++;
                
                // 添加页面之间的延迟
                usleep(1000000); // 1秒
            } else {
                $has_more = false;
                if ($page == 1) {
                    $_SESSION['batch_progress']['status'] = 'error';
                    $_SESSION['batch_progress']['message'] = "无法获取收藏夹信息或收藏夹为空";
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                }
            }
        }
        
        // 更新总视频数
        $_SESSION['batch_progress']['total'] = count($all_media);
        $_SESSION['batch_progress']['message'] = "共找到 {$_SESSION['batch_progress']['total']} 个视频，开始处理...";
        session_write_close();
        session_start();
        
        $added_count = 0;
        $skipped_count = 0;
        $error_count = 0;
        
        foreach ($all_media as $index => $media) {
            $current_number = $index + 1;
            $_SESSION['batch_progress']['processed'] = $current_number;
            $_SESSION['batch_progress']['current_video'] = $media['title'] ?? '未知视频';
            $_SESSION['batch_progress']['message'] = "正在处理第 {$current_number}/{$_SESSION['batch_progress']['total']} 个视频: " . ($media['title'] ?? '未知视频');
            session_write_close();
            session_start();
            
            if ($media['type'] != 2) { // 只处理视频类型
                $skipped_count++;
                $_SESSION['batch_progress']['skipped'] = $skipped_count;
                continue;
            }
            
            $bv_id = $media['bv_id'];
            
            // 检查是否已存在该BV号
            try {
                $checkStmt = $pdo->prepare("SELECT id FROM videos WHERE bv_id = ?");
                $checkStmt->execute([$bv_id]);
                
                if ($checkStmt->fetch()) {
                    $skipped_count++;
                    $_SESSION['batch_progress']['skipped'] = $skipped_count;
                    $_SESSION['batch_progress']['message'] = "跳过已存在的视频: {$bv_id}";
                    session_write_close();
                    session_start();
                    continue;
                }
            } catch (PDOException $e) {
                $error_count++;
                $_SESSION['batch_progress']['errors'] = $error_count;
                $_SESSION['batch_progress']['message'] = "数据库查询失败: {$bv_id}";
                session_write_close();
                session_start();
                continue;
            }
            
            // 获取视频标签api
            $tag_api_url = "https://api.bilibili.com/x/tag/archive/tags?bvid=" . $bv_id;
            $tag_ch = curl_init();
            curl_setopt($tag_ch, CURLOPT_URL, $tag_api_url);
            curl_setopt($tag_ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($tag_ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            $tag_response = curl_exec($tag_ch);
            curl_close($tag_ch);
            
            $tag_data = json_decode($tag_response, true);
            
            // 检查标签是否包含需要的字符
            $has_valid_tag = false;
            if ($tag_data && $tag_data['code'] === 0 && !empty($tag_data['data'])) {
                foreach ($tag_data['data'] as $tag) {
                    $tag_name = $tag['tag_name'];
                    if (strpos($tag_name, '哈基米') !== false || strpos($tag_name, '曼波') !== false || strpos($tag_name, '私人音乐') !== false || strpos($tag_name, '赛马娘') !== false || strpos($tag_name, '哈牛魔') !== false || strpos($tag_name, '叮咚鸡') !== false || strpos($tag_name, '活全家') !== false || strpos($tag_name, '胖宝宝') !== false || strpos($tag_name, '小白手套') !== false || strpos($tag_name, '诗歌剧') !== false || strpos($tag_name, '踩踩背') !== false || strpos($tag_name, '东海帝王') !== false || strpos($tag_name, '猫') !== false || strpos($tag_name, '米基哈') !== false || strpos($tag_name, '基米哈') !== false || strpos($tag_name, '耄耋') !== false || strpos($tag_name, '猫爹') !== false || strpos($tag_name, '猫咪') !== false || strpos($tag_name, '哈气') !== false || strpos($tag_name, '大狗叫') !== false || strpos($tag_name, '圆头') !== false || strpos($tag_name, '喵星人') !== false || strpos($tag_name, '爱猫tv') !== false || strpos($tag_name, '爱猫') !== false || strpos($tag_name, '踩背') !== false || strpos($tag_name, '爱猫fm') !== false || strpos($tag_name, '哈基米fm') !== false || strpos($tag_name, '哈吉米') !== false) {
                                    $has_valid_tag = true;
                                    break;
                    }
                }
            }
            
            if (!$has_valid_tag) {
                $skipped_count++;
                $_SESSION['batch_progress']['skipped'] = $skipped_count;
                $_SESSION['batch_progress']['message'] = "跳过标签不符合要求的视频: {$bv_id}";
                session_write_close();
                session_start();
                continue;
            }
            
            // 获取视频详细信息api
            $api_url = "https://api.bilibili.com/x/web-interface/view?bvid=" . $bv_id;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            $response = curl_exec($ch);
            curl_close($ch);
            
            $data = json_decode($response, true);
            
            if ($data && $data['code'] === 0) {
                $title = $data['data']['title'];
                $cover_url = str_replace('http://', 'https://', $data['data']['pic']);
                
                // 获取当前时间（东八区）
                $current_time = date('Y-m-d H:i:s');
                
                // 插入数据库
                try {
                    $stmt = $pdo->prepare("INSERT INTO videos (bv_id, title, cover_url, created_at) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$bv_id, $title, $cover_url, $current_time]);
                    $added_count++;
                    $_SESSION['batch_progress']['added'] = $added_count;
                    $_SESSION['batch_progress']['message'] = "成功添加: {$title}";
                    session_write_close();
                    session_start();
                } catch (PDOException $e) {
                    $error_count++;
                    $_SESSION['batch_progress']['errors'] = $error_count;
                    $_SESSION['batch_progress']['message'] = "数据库插入失败: {$title}";
                    session_write_close();
                    session_start();
                }
            } else {
                $error_count++;
                $_SESSION['batch_progress']['errors'] = $error_count;
                $_SESSION['batch_progress']['message'] = "获取视频信息失败: {$bv_id}";
                session_write_close();
                session_start();
            }
            
            // 添加延迟，避免请求过于频繁
            usleep(500000); // 0.5秒
        }
        
        // 完成处理
        $_SESSION['batch_progress']['status'] = 'completed';
        $_SESSION['batch_progress']['message'] = "批量添加完成！成功添加: $added_count 个视频, 跳过: $skipped_count 个视频, 错误: $error_count 个视频";
        exit();
    }
}

// 搜索参数
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// 分页参数
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 21;
$offset = ($page - 1) * $perPage;

// 获取视频总数和视频列表
$whereClause = '';
$params = [];

if (!empty($search)) {
    $whereClause = " WHERE title LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

try {
    // 获取视频总数
    $totalStmt = $pdo->prepare("SELECT COUNT(*) as total FROM videos" . $whereClause);
    foreach ($params as $key => $value) {
        $totalStmt->bindValue($key, $value);
    }
    $totalStmt->execute();
    $totalRow = $totalStmt->fetch(PDO::FETCH_ASSOC);
    $totalVideos = $totalRow['total'];

    // 网站的评论计数
    $sql = "
        SELECT v.*, 
               (SELECT COUNT(*) FROM comments c WHERE c.video_id = v.id) AS comment_count 
        FROM videos v 
        " . $whereClause . " 
        ORDER BY v.is_pinned DESC, v.created_at DESC 
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "数据库查询失败: " . $e->getMessage();
}

// 计算总页数
$totalPages = ceil($totalVideos / $perPage);

// 获取会话消息
$success = isset($_SESSION['success']) ? $_SESSION['success'] : null;
$error = isset($_SESSION['error']) ? $_SESSION['error'] : null;

// 清除会话消息
unset($_SESSION['success']);
unset($_SESSION['error']);

// 获取视频总数的请求
if (isset($_GET['action']) && $_GET['action'] == 'get_total') {
    try {
        $totalStmt = $pdo->query("SELECT COUNT(*) as total FROM videos");
        $totalRow = $totalStmt->fetch(PDO::FETCH_ASSOC);
        echo $totalRow['total'];
        exit();
    } catch (PDOException $e) {
        echo '0';
        exit();
    }
}
/**
 * 
 * 
 * php区域结束
 * 
 * 
 */
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>咪友之家 - 哈基米_喔妈激励曼波</title><!--网站标题-->
    <link rel="shortcut icon" href="favicon.ico" /><!--网站图标-->
    <link rel="stylesheet" href="css/mdui.min.css"><!--引入mdui的css-->
    <link rel="stylesheet" href="css/index_index.css"><!--引入自己写的css-->
    <meta name="keywords" content="咪友之家">
    <meta name="description" content="哈基米,曼波,鬼畜,私人音乐,赛马娘,哈牛魔,叮咚鸡,活全家,胖宝宝,小白手套,诗歌剧,踩踩背,东海帝王,猫,米基哈,基米哈,耄耋,猫爹,猫咪,哈气,大狗叫,圆头,喵星人,爱猫tv,爱猫,踩背,爱猫fm,哈基米fm,哈吉米">
    
    <script defer src="https://0721umami.icu/random-string.js" data-website-id="87ef31e4-1aeb-4499-9710-b60df1bb26bf"></script><!--网站统计-->
    
</head>
<body class="mdui-appbar-with-toolbar">
    <!--顶部工具栏开始-->
    <div class="mdui-appbar mdui-appbar-fixed">
        <div class="mdui-toolbar mdui-color-pink">
            
            <!--左上角标题-->
            <h1>
                <a href="https://mijiha.icu/" class="mdui-typo-title" style="font-weight:900; text-decoration: none; color: white;">咪友之家</a>
                <span id="total-videos" class="mdui-typo-title" style="font-weight:900; color: white; font-size:14px;"></span>
            </h1>
            
            <div class="mdui-toolbar-spacer"></div><!--工具栏占位符-->
            
            <!-- 搜索按钮 -->
            <button id="search-button" class="mdui-btn mdui-btn-icon mdui-ripple mdui-ripple-white" mdui-tooltip="{content: '搜索视频'}">
                <i class="mdui-icon material-icons">search</i>
            </button>
            
            <!-- 夜间模式切换按钮 -->
            <button id="theme-toggle" class="mdui-btn mdui-btn-icon mdui-ripple mdui-ripple-white" mdui-tooltip="{content: '日夜模式'}">
                <i class="mdui-icon material-icons" id="theme-icon">brightness_4</i>
            </button>
            
            <!-- 添加视频按钮 -->
            <button id="add-button" class="mdui-btn mdui-btn-icon mdui-ripple mdui-ripple-white" mdui-tooltip="{content: '添加视频'}">
                <i class="mdui-icon material-icons">add</i>
            </button>
            
            <!-- 批量添加按钮 -->
            <button id="batch-add-button" class="mdui-btn mdui-btn-icon mdui-ripple mdui-ripple-white" mdui-tooltip="{content: '批量添加'}">
                <i class="mdui-icon material-icons">playlist_add</i>
            </button>
            
            <!-- 关于网站 -->
            <button class="mdui-btn mdui-btn-icon mdui-ripple mdui-ripple-white" mdui-dialog="{target: '#gy-dialog'}" mdui-tooltip="{content: '关于网站'}">
                <i class="mdui-icon material-icons">perm_contact_calendar</i>
            </button>
        </div>
    </div>
    <!--顶部工具栏结束-->
    
    <div style="height: 64px;"></div><!-- 占位符必须添加，因为工具栏是fixed定位 -->
    
    <!--卡片区域开始-->
    <div class="app-container">
        <div class="mdui-container">
            <!--链接不上数据库或者错误时显示开始-->
            <?php if ($error): ?>
                <div class="mdui-alert mdui-alert-error mdui-m-b-4">
                    <i class="mdui-icon material-icons">error</i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="mdui-alert mdui-alert-success mdui-m-b-4">
                    <i class="mdui-icon material-icons">check_circle</i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            <!--链接不上数据库或者错误时显示结束-->
            
            <!--链接上数据库时开始-->
            <div class="mdui-row">
                <!--没有添加视频开始-->
                <?php if (empty($videos)): ?>
                    <div class="mdui-col-xs-12">
                        <div class="empty-state">
                            <i class="mdui-icon material-icons">playlist_add</i>
                            <h2 class="mdui-typo-title"><?php echo empty($search) ? '还没有添加视频' : '没有找到相关视频'; ?></h2>
                            <p><?php echo empty($search) ? '点击右上角添加按钮添加视频' : '请尝试其他搜索关键词'; ?></p>
                        </div>
                    </div>
                <?php else: ?>
                <!--没有添加视频结束-->
                
                <!--有视频数据开始-->
                <?php foreach ($videos as $video): ?>
                    <div class="mdui-col-xs-12 mdui-col-sm-6 mdui-col-md-4 mdui-m-b-2">
                        <div class="mdui-card video-card" onclick="window.location.href='detail.php?id=<?php echo $video['id']; ?>'"><!--点击添加到的网址-->
                            <div class="mdui-card-media">
                                <img class="cover-img" src="<?php echo htmlspecialchars($video['cover_url']); ?>" alt="<?php echo htmlspecialchars($video['title']); ?>" referrerpolicy="no-referrer"><!--封面图片-->
                                <div class="mdui-card-menu">
                                    <?php if ($video['is_pinned']): ?>
                                        <div class="bvid-badge">置顶</div>
                                    <?php endif; ?>
                                    <span class="bvid-badge">评论数：<?php echo $video['comment_count']; ?></span>
                                </div>
                                <div class="mdui-card-media-covered mdui-card-media-covered-gradient">
                                    <div class="mdui-card-primary">
                                        <div class="mdui-card-primary-title" style="font-weight:900;"><?php echo htmlspecialchars($video['title']); ?></div><!--视频标题-->
                                    </div>
                                </div>
                            </div>
                            <div class="card-content">
                                <div class="bvid-badge mdui-float-left">BV号：<?php echo htmlspecialchars($video['bv_id']); ?></div><!--视频BV号-->
                                <div class="bvid-badge mdui-float-right"><?php echo date('Y-m-d H:i', strtotime($video['created_at'])); ?></div><!--添加这个视频的时间-->
                                </br>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php endif; ?>
                <!--有视频数据结束-->
            </div>
            <!--链接上数据库时结束-->
            
            <!--分页控件开始-->
            <?php if ($totalPages > 1): ?>
                <div class="page-info">
                    第 <?php echo $page; ?> 页 / 共 <?php echo $totalPages; ?> 页<!--总页数-->
                </div>
                <!--上一页下一页的按钮开始-->
                <div class="pagination">
                    <!--上一页的按钮开始-->
                    <?php if ($page > 1): ?>
                        <a href="?page=1&search=<?php echo urlencode($search); ?>" class="mdui-btn mdui-btn-icon mdui-color-pink">
                            <i class="mdui-icon material-icons">first_page</i>
                        </a>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="mdui-btn mdui-btn-icon mdui-color-pink">
                            <i class="mdui-icon material-icons">chevron_left</i>
                        </a>
                    <?php endif; ?>
                    <!--上一页的按钮结束-->
                    
                    <!--显示页码开始-->
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="mdui-btn mdui-btn-icon <?php echo $i == $page ? 'mdui-color-pink' : 'mdui-color-grey-300'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="mdui-btn mdui-btn-icon mdui-color-pink">
                            <i class="mdui-icon material-icons">chevron_right</i>
                        </a>
                        <a href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search); ?>" class="mdui-btn mdui-btn-icon mdui-color-pink">
                            <i class="mdui-icon material-icons">last_page</i>
                        </a>
                    <?php endif; ?>
                    <!--显示页码结束-->
                </div>
                <!--上一页下一页的按钮结束-->
            <?php endif; ?>
        </div>
    </div>
    <!--卡片区域结束-->
    
    <!--添加视频对话框开始-->
    <div class="mdui-dialog" id="add-dialog">
        <div class="mdui-dialog-title">添加B站视频</div>
        <div class="mdui-dialog-content">
            <form id="add-form" method="POST">
                <div class="mdui-textfield mdui-textfield-floating-label">
                    <label class="mdui-textfield-label">请输入B站视频BV号</label>
                    <input class="mdui-textfield-input" type="text" name="bv_id" required pattern="BV[a-zA-Z0-9]{10}"/>
                    <div class="mdui-textfield-error">BV号格式不正确（如：BV1gRTqzdERJ）</div>
                    <div class="mdui-textfield-helper">例如：BV1gRTqzdERJ</div>
                </div>
            </form>
        </div>
        <div class="mdui-dialog-actions">
            <button class="mdui-btn mdui-ripple" id="cancel-button">取消</button>
            <button class="mdui-btn mdui-ripple mdui-color-pink" type="submit" form="add-form">添加</button>
        </div>
    </div>
    <!--添加视频对话框结束-->
    
    <!--批量添加视频对话框开始-->
    <div class="mdui-dialog" id="batch-add-dialog">
        <div class="mdui-dialog-title">批量添加哔站视频</div>
        <div class="mdui-dialog-content">
            <form id="batch-add-form" method="POST">
                <div class="mdui-textfield mdui-textfield-floating-label">
                    <label class="mdui-textfield-label">请输入哔站收藏夹ID或链接</label>
                    <input class="mdui-textfield-input" type="text" name="favorite_id" required/>
                    <div class="mdui-textfield-error">请输入有效的哔站收藏夹ID</div>
                    <span class="mdui-textfield-helper">例如: 3681188610</span>
                </div>
            </form>
        </div>
        <div class="mdui-dialog-actions">
            <button class="mdui-btn mdui-ripple" id="batch-cancel-button">取消</button>
            <button class="mdui-btn mdui-ripple mdui-color-pink" id="batch-submit-button" type="button">批量添加</button>
        </div>
    </div>
    <!--批量添加视频对话框结束-->
    
    <!--批量添加进度对话框开始-->
    <div class="mdui-dialog" id="batch-progress-dialog">
        <div class="mdui-dialog-title">批量添加进度</div>
        <div class="mdui-dialog-content">
            <div id="progress-container">
                <div class="mdui-progress">
                    <div class="mdui-progress-determinate" id="progress-bar" style="width: 0%"></div>
                </div>
                </br>
                <div id="progress-text">准备开始...</div></br>
                <div id="progress-details">
                    <div style="color: red;">添加中....添加过程请勿刷新网页，添加结束之后再刷新</div></br>
                    <div>总视频数: <span id="progress-total">0</span></div></br>
                    <div>已处理: <span id="progress-processed">0</span></div></br>
                    <div>成功添加: <span id="progress-added">0</span></div></br>
                    <div>已跳过: <span id="progress-skipped">0</span></div></br>
                    <div>错误: <span id="progress-errors">0</span></div></br>
                    <div>当前视频: <span id="progress-current">-</span></div></br>
                </div>
            </div>
        </div>
        <div class="mdui-dialog-actions">
            <button class="mdui-btn mdui-ripple" id="progress-close-button" style="display:none;">关闭</button>
        </div>
    </div>
    <!--批量添加进度对话框结束-->
    
    <!--搜索对话框开始-->
    <div class="mdui-dialog" id="search-dialog">
        <div class="mdui-dialog-title">搜索视频</div>
        <div class="mdui-dialog-content">
            <form id="search-form" method="GET" action="index.php">
                <div class="mdui-textfield mdui-textfield-floating-label">
                    <label class="mdui-textfield-label">请输入视频标题关键词</label>
                    <input class="mdui-textfield-input" type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"/>
                    <div class="mdui-textfield-helper"></div>
                </div>
            </form>
        </div>
        <div class="mdui-dialog-actions">
            <button class="mdui-btn mdui-ripple" id="search-cancel-button">取消</button>
            <button class="mdui-btn mdui-ripple mdui-color-pink" type="submit" form="search-form">搜索</button>
        </div>
    </div>
    <!--搜索对话框结束-->
    
    <!--关于对话框开始-->
    <div class="mdui-dialog"  id="gy-dialog">
      <div class="mdui-dialog-content">
        <div class="mdui-dialog-title">关于网站</div>
        <h3>网站做着玩的，大家不要发表奇奇怪怪的评论，比如月抛涉政之类的，看到的话我会删，其他涉黄无所谓。</h3></br>
        <mark>2025年7月22日：网站初步完成，能正常添加哔站视频、日夜模式切换、完成了搜索功能。</mark></br>
        <mark>2025年7月23日：卡片的右上角显示每个视频在网站的评论数量、视频置顶功能(需要在数据库设置)、点击出视频详情能播放视频。</mark></br>
        <mark>2025年9月08日：批量添加哔站收藏夹视频。</mark></br>
        <p>下一步：使添加的视频能自动播放，播放完一个自动播放下一个，就像哔站的"播放全部"一样。ps:可能会很难，但是也不是不能实现</p></br>
      </div>
      <div class="mdui-dialog-actions">
        <button class="mdui-btn mdui-ripple" onclick="window.location.href='https://nn0721.icu'">0721_Galgame</button>
        <button class="mdui-btn mdui-ripple" onclick="window.location.href='https://dm0721.icu'">0721_动漫</button>
        <button class="mdui-btn mdui-ripple" onclick="window.location.href='https://lt0721.icu'">0721_论坛</button>
        <button class="mdui-btn mdui-ripple" onclick="window.location.href='https://github.com/mingrixiangnai/mijiha'">开源代码</button>
        <button class="mdui-btn mdui-ripple" onclick="window.location.href='https://0721umami.icu/share/bhRBEczFlxAeN5Tk/mijiha.icu'">网站统计</button>
      </div>
    </div>
    <!--关于对话框结束-->

    <script src="js/mdui.min.js"></script><!--引入mdui的js-->
    <script src="js/index_index.js"></script><!--引入自己写的js-->
</body>
</html>
