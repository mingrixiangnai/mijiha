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

// 获取视频ID
$video_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 获取视频信息
$video = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM videos WHERE id = ?");
    $stmt->execute([$video_id]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("数据库查询失败: " . $e->getMessage());
}

// 如果视频不存在
if (!$video) {
    die("视频不存在");
}

// 处理评论提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $user_name = isset($_POST['user_name']) ? trim($_POST['user_name']) : '访客';
    $content = trim($_POST['content']);
    
    if (!empty($content)) {
        // 获取当前时间（东八区）
        $current_time = date('Y-m-d H:i:s');
        
        try {
            $stmt = $pdo->prepare("INSERT INTO comments (video_id, user_name, content, created_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$video_id, $user_name, $content, $current_time]);
            
            $_SESSION['comment_success'] = "评论发表成功！";
            header("Location: detail.php?id=" . $video_id);
            exit();
        } catch (PDOException $e) {
            $_SESSION['comment_error'] = "评论提交失败: " . $e->getMessage();
            header("Location: detail.php?id=" . $video_id);
            exit();
        }
    } else {
        $_SESSION['comment_error'] = "评论内容不能为空";
        header("Location: detail.php?id=" . $video_id);
        exit();
    }
}

// 获取评论列表
$comments = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM comments WHERE video_id = ? ORDER BY created_at DESC");
    $stmt->execute([$video_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $comment_error = "评论加载失败: " . $e->getMessage();
}

// 获取会话消息
$comment_success = isset($_SESSION['comment_success']) ? $_SESSION['comment_success'] : null;
$comment_error = isset($_SESSION['comment_error']) ? $_SESSION['comment_error'] : null;

// 清除会话消息
unset($_SESSION['comment_success']);
unset($_SESSION['comment_error']);
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
    <title>咪友之家 - <?php echo htmlspecialchars($video['title']); ?></title><!--网站标题-->
    <link rel="shortcut icon" href="favicon.ico" /><!--网站图标-->
    <link rel="stylesheet" href="css/mdui.min.css"><!--引入mdui的css-->
    <link rel="stylesheet" href="css/detail_detail.css"><!--引入自己写的css-->
    <meta name="keywords" content="咪友之家">
    <meta name="description" content="哈基米,曼波,鬼畜,私人音乐,赛马娘,哈牛魔,叮咚鸡,活全家,胖宝宝,小白手套,诗歌剧,核酸,踩踩背,东海帝王,猫,米基哈,基米哈,耄耋,猫爹,猫咪,哈气,应激,大狗叫,圆头,喵星人,爱猫tv,爱猫,Doro,踩背,爱猫fm,哈基米fm,哈吉米">
    
    <script defer src="https://0721umami.icu/random-string.js" data-website-id="87ef31e4-1aeb-4499-9710-b60df1bb26bf"></script><!--网站统计-->
    
</head>
<body class="mdui-appbar-with-toolbar">
    <!--顶部工具栏开始-->
    <div class="mdui-appbar mdui-appbar-fixed">
        <div class="mdui-toolbar mdui-color-pink">
            <a href="javascript:history.back(-1)" class="mdui-btn mdui-btn-icon">
                <i class="mdui-icon material-icons">arrow_back</i><!--返回按钮-->
            </a>
            <h1><span class="mdui-typo-title" style="font-weight:900; color: white;"><?php echo htmlspecialchars($video['title']); ?> - 视频详情</span></h1>
            <div class="mdui-toolbar-spacer"></div>
            
            <!--夜间模式切换按钮-->
            <button id="theme-toggle" class="mdui-btn mdui-btn-icon mdui-ripple mdui-ripple-white">
                <i class="mdui-icon material-icons" id="theme-icon">brightness_4</i>
            </button>
        </div>
    </div>
    <!--顶部工具栏结束-->
    
    <!-- 占位符必须添加，因为工具栏是fixed定位 -->
    <div style="height: 64px;"></div>
    
    <!--卡片区域开始-->
    <div class="mdui-container">
        <div class="video-container">
            <div class="mdui-video-container">
                <iframe src="//bilibili.com/blackboard/html5mobileplayer.html?bvid=<?php echo htmlspecialchars($video['bv_id']); ?>" allowfullscreen="true"></iframe><!--加载哔站BV号视频api-->
            </div>
            
            <div class="video-info">
                <div class="video-title" style="font-weight:900;"><?php echo htmlspecialchars($video['title']); ?></div><!--视频标题-->
                <div class="video-meta">
                    <div>
                        <span class="bvid-badge mdui-float-left">BV号: <?php echo htmlspecialchars($video['bv_id']); ?></span><!--视频BV号-->
                    </div>
                        <div class="bvid-badge mdui-float-right"><?php echo date('Y-m-d H:i', strtotime($video['created_at'])); ?><!--添加这个视频的时间-->
                    </div>
                </div>
                
                <div>
                    <a href="https://www.bilibili.com/video/<?php echo htmlspecialchars($video['bv_id']); ?>" class="mdui-btn mdui-ripple mdui-color-pink mdui-btn-block" target="_blank">
                        <i class="mdui-icon material-icons">play_arrow</i>跳转到哔站
                    </a>
                </div>
                
            </div>
        </div>
        
        <!--评论区开始-->
        <div class="comments-container mdui-hoverable">
            <h1 class="comment-title">评论区</h1>
            
            <!--链接不上数据库或者错误时显示开始-->
            <?php if ($comment_success): ?>
                <div class="mdui-alert mdui-alert-success mdui-m-b-4">
                    <i class="mdui-icon material-icons">check_circle</i><?php echo htmlspecialchars($comment_success); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($comment_error): ?>
                <div class="mdui-alert mdui-alert-error mdui-m-b-4">
                    <i class="mdui-icon material-icons">error</i><?php echo htmlspecialchars($comment_error); ?>
                </div>
            <?php endif; ?>
            <!--链接不上数据库或者错误时显示结束-->
            
            <!--链接上数据库时开始-->
            <div class="comment-form">
                <form method="POST">
                    <div class="mdui-textfield">
                        <label>您的昵称</label>
                        <input class="mdui-textfield-input" type="text" name="user_name" maxlength="10" placeholder="请输入...." required>
                    </div>
                    <div class="mdui-textfield mdui-textfield-floating-label">
                        <label>评论内容</label>
                        <input class="mdui-textfield-input" name="content" maxlength="100" placeholder="请输入...." required>
                    </div>
                    <button type="submit" class="mdui-btn mdui-ripple mdui-color-pink mdui-btn-block">
                        <i class="mdui-icon material-icons">send</i> 提交评论
                    </button>
                </form>
            </div>
            
            <!--没评论时的显示开始-->
            <?php if (empty($comments)): ?>
                <div class="empty-comments">
                    <i class="mdui-icon material-icons">forum</i>
                    <h3>还没有评论</h3>
                    <p>成为第一个评论的人</p>
                </div>
            <?php else: ?>
            <!--没评论时的显示结束-->
            
            <!--有评论时的显示开始-->
            <?php foreach ($comments as $comment): ?>
                <div class="comment-item">
                    <div class="comment-avatar">
                        <?php echo mb_substr($comment['user_name'], 0, 1); ?>
                    </div>
                    <div class="comment-content">
                        <div class="comment-header">
                            <div class="comment-user">昵称：<?php echo htmlspecialchars($comment['user_name']); ?></div>
                            <div class="comment-time"><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></div>
                        </div>
                        <div class="comment-text">内容：<?php echo nl2br(htmlspecialchars($comment['content'])); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
            <!--有评论时的显示结束-->
            <?php endif; ?>
            <!--链接上数据库时结束-->
        </div>
        <!--评论区结束-->
    </div>
    <!--卡片区域结束-->
    
    <script src="js/mdui.min.js"></script><!--引入mdui的js-->
    <script src="js/detail_detail.js"></script><!--引入自己写的js-->
</body>
</html>