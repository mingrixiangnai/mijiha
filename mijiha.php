<?php
/**
 * 咪友之家 - 后台管理页面
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
    $pdo->exec("SET time_zone = '+08:00';");
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

// 每页显示数量
$perPage = 21;

// 获取当前页
$videoPage = isset($_GET['video_page']) ? max(1, intval($_GET['video_page'])) : 1;
$commentPage = isset($_GET['comment_page']) ? max(1, intval($_GET['comment_page'])) : 1;

// 处理管理操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 删除视频
    if (isset($_POST['delete_video'])) {
        $video_id = intval($_POST['video_id']);
        
        try {
            // 先删除相关评论
            $stmt = $pdo->prepare("DELETE FROM comments WHERE video_id = ?");
            $stmt->execute([$video_id]);
            
            // 删除视频
            $stmt = $pdo->prepare("DELETE FROM videos WHERE id = ?");
            $stmt->execute([$video_id]);
            
            $_SESSION['admin_message'] = "视频删除成功！";
        } catch (PDOException $e) {
            $_SESSION['admin_message'] = "删除失败: " . $e->getMessage();
        }
    }
    
    // 删除评论
    if (isset($_POST['delete_comment'])) {
        $comment_id = intval($_POST['comment_id']);
        
        try {
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$comment_id]);
            
            $_SESSION['admin_message'] = "评论删除成功！";
        } catch (PDOException $e) {
            $_SESSION['admin_message'] = "删除失败: " . $e->getMessage();
        }
    }
    
    // 编辑视频
    if (isset($_POST['edit_video'])) {
        $video_id = intval($_POST['video_id']);
        $title = trim($_POST['title']);
        $cover_url = trim($_POST['cover_url']);
        $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("UPDATE videos SET title = ?, cover_url = ?, is_pinned = ? WHERE id = ?");
            $stmt->execute([$title, $cover_url, $is_pinned, $video_id]);
            
            $_SESSION['admin_message'] = "视频信息更新成功！";
        } catch (PDOException $e) {
            $_SESSION['admin_message'] = "更新失败: " . $e->getMessage();
        }
    }
    
    // 编辑评论
    if (isset($_POST['edit_comment'])) {
        $comment_id = intval($_POST['comment_id']);
        $user_name = trim($_POST['user_name']);
        $content = trim($_POST['content']);
        
        try {
            $stmt = $pdo->prepare("UPDATE comments SET user_name = ?, content = ? WHERE id = ?");
            $stmt->execute([$user_name, $content, $comment_id]);
            
            $_SESSION['admin_message'] = "评论更新成功！";
        } catch (PDOException $e) {
            $_SESSION['admin_message'] = "更新失败: " . $e->getMessage();
        }
    }
    
    // 刷新页面
    header("Location: mijiha.php");
    exit;
}

// 获取视频数据
$videos = [];
$totalVideos = 0;
$totalVideoPages = 1;

try {
    // 获取视频总数
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM videos");
    $totalVideos = $stmt->fetchColumn();
    $totalVideoPages = ceil($totalVideos / $perPage);
    
    // 获取当前页视频
    $videoOffset = ($videoPage - 1) * $perPage;
    $stmt = $pdo->prepare("SELECT * FROM videos ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $videoOffset, PDO::PARAM_INT);
    $stmt->execute();
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $admin_error = "视频加载失败: " . $e->getMessage();
}

// 获取评论数据
$comments = [];
$totalComments = 0;
$totalCommentPages = 1;

try {
    // 获取评论总数
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM comments");
    $totalComments = $stmt->fetchColumn();
    $totalCommentPages = ceil($totalComments / $perPage);
    
    // 获取当前页评论
    $commentOffset = ($commentPage - 1) * $perPage;
    $stmt = $pdo->prepare("SELECT c.*, v.title AS video_title 
                           FROM comments c 
                           LEFT JOIN videos v ON c.video_id = v.id 
                           ORDER BY c.created_at DESC 
                           LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $commentOffset, PDO::PARAM_INT);
    $stmt->execute();
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $admin_error = "评论加载失败: " . $e->getMessage();
}

// 获取消息
$admin_message = isset($_SESSION['admin_message']) ? $_SESSION['admin_message'] : null;
unset($_SESSION['admin_message']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>咪友之家 - 后台管理页面</title>
    <link rel="stylesheet" href="css/mdui.min.css">
    <script src="js/mdui.min.js"></script>
    
    <script defer src="https://0721umami.icu/random-string.js" data-website-id="87ef31e4-1aeb-4499-9710-b60df1bb26bf"></script><!--网站统计-->
    
</head>
<body class="mdui-appbar-with-toolbar">
    <!-- 顶部工具栏 -->
    <div class="mdui-appbar mdui-appbar-fixed">
        <div class="mdui-toolbar mdui-color-pink">
            <a href="https://mijiha.icu/mijiha.php" class="mdui-typo-title" style="font-weight:900; text-decoration: none; color: white;">咪友之家 - 后台管理页面</a>
            <div class="mdui-toolbar-spacer"></div>
            
            <!-- 夜间模式切换按钮 -->
            <button id="theme-toggle" class="mdui-btn mdui-btn-icon mdui-ripple mdui-ripple-white">
                <i class="mdui-icon material-icons" id="theme-icon">brightness_4</i>
            </button>
        </div>
    </div>
    
    <!-- 占位符 -->
    <div style="height: 64px;"></div>
    
    <!-- 主内容区 -->
    <div class="mdui-container">
        <?php if ($admin_message): ?>
            <div class="mdui-alert mdui-alert-success mdui-m-b-4">
                <i class="mdui-icon material-icons">check_circle</i><?php echo htmlspecialchars($admin_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($admin_error)): ?>
            <div class="mdui-alert mdui-alert-error mdui-m-b-4">
                <i class="mdui-icon material-icons">error</i><?php echo htmlspecialchars($admin_error); ?>
            </div>
        <?php endif; ?>
        
        <!-- 选项卡 -->
        <div class="mdui-tab mdui-tab-scrollable" mdui-tab>
            <a href="#video-panel" class="mdui-ripple">视频管理</a>
            <a href="#comment-panel" class="mdui-ripple">评论管理</a>
        </div>
        
        <!-- 视频管理面板 -->
        <div id="video-panel">
            <div class="mdui-row">
                <div class="mdui-col-md-12">
                    <h2 class="mdui-typo-title mdui-text-color-pink">视频管理</h2>
                </div>
            </div>
            
            <div class="mdui-m-b-2">
                <span class="mdui-typo-caption">
                    共 <?php echo $totalVideos; ?> 个视频 | 
                    第 <?php echo $videoPage; ?> 页/共 <?php echo $totalVideoPages; ?> 页
                </span>
            </div>
            
            <div class="mdui-table-fluid">
                <table class="mdui-table mdui-table-hoverable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>BV号</th>
                            <th>标题</th>
                            <th>封面</th>
                            <th>置顶</th>
                            <th>添加时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($videos)): ?>
                            <tr>
                                <td colspan="7" class="mdui-text-center">没有找到视频</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($videos as $video): ?>
                            <tr>
                                <td><?php echo $video['id']; ?></td>
                                <td><?php echo htmlspecialchars($video['bv_id']); ?></td>
                                <td><?php echo htmlspecialchars($video['title']); ?></td>
                                <td>
                                    <a href="detail.php?id=<?php echo $video['id']; ?>" target="_blank" mdui-tooltip="{content: '查看视频页面'}">
                                        <img src="<?php echo htmlspecialchars($video['cover_url']); ?>" alt="封面" style="max-width: 100px;" referrerpolicy="no-referrer">
                                    </a>
                                </td>
                                <td>
                                    <?php echo $video['is_pinned'] ? '是' : '否'; ?>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($video['created_at'])); ?></td>
                                <td>
                                    <button class="mdui-btn mdui-btn-icon mdui-color-blue mdui-ripple" 
                                            mdui-dialog="{target: '#edit-video-<?php echo $video['id']; ?>'}">
                                        <i class="mdui-icon material-icons">edit</i>
                                    </button>
                                    <button class="mdui-btn mdui-btn-icon mdui-color-red mdui-ripple" 
                                            mdui-dialog="{target: '#delete-video-<?php echo $video['id']; ?>'}">
                                        <i class="mdui-icon material-icons">delete</i>
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- 编辑视频对话框 -->
                            <div class="mdui-dialog" id="edit-video-<?php echo $video['id']; ?>">
                                <div class="mdui-dialog-title">编辑视频</div>
                                <div class="mdui-dialog-content">
                                    <form method="POST">
                                        <input type="hidden" name="video_id" value="<?php echo $video['id']; ?>">
                                        <div class="mdui-textfield">
                                            <label class="mdui-textfield-label">标题</label>
                                            <input class="mdui-textfield-input" type="text" name="title" 
                                                   value="<?php echo htmlspecialchars($video['title']); ?>" required>
                                        </div>
                                        <div class="mdui-textfield">
                                            <label class="mdui-textfield-label">封面URL</label>
                                            <input class="mdui-textfield-input" type="text" name="cover_url" 
                                                   value="<?php echo htmlspecialchars($video['cover_url']); ?>" required>
                                        </div>
                                        <div class="mdui-textfield">
                                            <label class="mdui-checkbox">
                                                <input type="checkbox" name="is_pinned" <?php echo $video['is_pinned'] ? 'checked' : ''; ?>>
                                                <i class="mdui-checkbox-icon"></i>
                                                置顶
                                            </label>
                                        </div>
                                        <div class="mdui-dialog-actions">
                                            <button class="mdui-btn mdui-ripple" mdui-dialog-close>取消</button>
                                            <button type="submit" name="edit_video" class="mdui-btn mdui-ripple mdui-color-pink">保存</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- 删除视频对话框 -->
                            <div class="mdui-dialog" id="delete-video-<?php echo $video['id']; ?>">
                                <div class="mdui-dialog-title">删除视频</div>
                                <div class="mdui-dialog-content">
                                    确定要删除视频 "<?php echo htmlspecialchars($video['title']); ?>" 吗？<br>
                                    此操作将同时删除该视频的所有评论！
                                </div>
                                <div class="mdui-dialog-actions">
                                    <button class="mdui-btn mdui-ripple" mdui-dialog-close>取消</button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="video_id" value="<?php echo $video['id']; ?>">
                                        <button type="submit" name="delete_video" class="mdui-btn mdui-ripple mdui-color-red">删除</button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- 视频分页控件 -->
            <?php if ($totalVideoPages > 1): ?>
                <div class="mdui-pagination mdui-m-t-2 mdui-m-b-4">
                    <!-- 上一页 -->
                    <?php if ($videoPage > 1): ?>
                        <a href="?video_page=<?php echo $videoPage - 1; ?>&comment_page=<?php echo $commentPage; ?>" 
                           class="mdui-btn mdui-ripple">
                            <i class="mdui-icon material-icons">chevron_left</i>
                        </a>
                    <?php endif; ?>
                    
                    <!-- 页码 -->
                    <?php
                    $startPage = max(1, $videoPage - 2);
                    $endPage = min($totalVideoPages, $videoPage + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <a href="?video_page=<?php echo $i; ?>&comment_page=<?php echo $commentPage; ?>" 
                           class="mdui-btn mdui-ripple <?php echo $i == $videoPage ? 'mdui-color-pink' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <!-- 下一页 -->
                    <?php if ($videoPage < $totalVideoPages): ?>
                        <a href="?video_page=<?php echo $videoPage + 1; ?>&comment_page=<?php echo $commentPage; ?>" 
                           class="mdui-btn mdui-ripple">
                            <i class="mdui-icon material-icons">chevron_right</i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 评论管理面板 -->
        <div id="comment-panel" style="display:none;">
            <div class="mdui-row">
                <div class="mdui-col-md-12">
                    <h2 class="mdui-typo-title mdui-text-color-pink">评论管理</h2>
                </div>
            </div>
            
            <div class="mdui-m-b-2">
                <span class="mdui-typo-caption">
                    共 <?php echo $totalComments; ?> 条评论 | 
                    第 <?php echo $commentPage; ?> 页/共 <?php echo $totalCommentPages; ?> 页
                </span>
            </div>
            
            <div class="mdui-table-fluid">
                <table class="mdui-table mdui-table-hoverable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>视频ID</th>
                            <th>视频标题</th>
                            <th>用户名</th>
                            <th>评论内容</th>
                            <th>评论时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($comments)): ?>
                            <tr>
                                <td colspan="7" class="mdui-text-center">没有找到评论</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                            <tr>
                                <td><?php echo $comment['id']; ?></td>
                                <td><?php echo $comment['video_id']; ?></td>
                                <td title="<?php echo htmlspecialchars($comment['video_title']); ?>">
                                    <?php echo mb_substr(htmlspecialchars($comment['video_title']), 0, 10); ?>...
                                </td>
                                <td><?php echo htmlspecialchars($comment['user_name']); ?></td>
                                <td title="<?php echo htmlspecialchars($comment['content']); ?>">
                                    <?php echo mb_substr(htmlspecialchars($comment['content']), 0, 15); ?>...
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></td>
                                <td>
                                    <button class="mdui-btn mdui-btn-icon mdui-color-blue mdui-ripple" 
                                            mdui-dialog="{target: '#edit-comment-<?php echo $comment['id']; ?>'}">
                                        <i class="mdui-icon material-icons">edit</i>
                                    </button>
                                    <button class="mdui-btn mdui-btn-icon mdui-color-red mdui-ripple" 
                                            mdui-dialog="{target: '#delete-comment-<?php echo $comment['id']; ?>'}">
                                        <i class="mdui-icon material-icons">delete</i>
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- 编辑评论对话框 -->
                            <div class="mdui-dialog" id="edit-comment-<?php echo $comment['id']; ?>">
                                <div class="mdui-dialog-title">编辑评论</div>
                                <div class="mdui-dialog-content">
                                    <form method="POST">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                        <div class="mdui-textfield">
                                            <label class="mdui-textfield-label">用户名</label>
                                            <input class="mdui-textfield-input" type="text" name="user_name" 
                                                   value="<?php echo htmlspecialchars($comment['user_name']); ?>" required>
                                        </div>
                                        <div class="mdui-textfield">
                                            <label class="mdui-textfield-label">评论内容</label>
                                            <textarea class="mdui-textfield-input" name="content" rows="3" required><?php 
                                                echo htmlspecialchars($comment['content']); 
                                            ?></textarea>
                                        </div>
                                        <div class="mdui-dialog-actions">
                                            <button class="mdui-btn mdui-ripple" mdui-dialog-close>取消</button>
                                            <button type="submit" name="edit_comment" class="mdui-btn mdui-ripple mdui-color-pink">保存</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- 删除评论对话框 -->
                            <div class="mdui-dialog" id="delete-comment-<?php echo $comment['id']; ?>">
                                <div class="mdui-dialog-title">删除评论</div>
                                <div class="mdui-dialog-content">
                                    确定要删除这条评论吗？
                                </div>
                                <div class="mdui-dialog-actions">
                                    <button class="mdui-btn mdui-ripple" mdui-dialog-close>取消</button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                        <button type="submit" name="delete_comment" class="mdui-btn mdui-ripple mdui-color-red">删除</button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- 评论分页控件 -->
            <?php if ($totalCommentPages > 1): ?>
                <div class="mdui-pagination mdui-m-t-2 mdui-m-b-4">
                    <!-- 上一页 -->
                    <?php if ($commentPage > 1): ?>
                        <a href="?comment_page=<?php echo $commentPage - 1; ?>&video_page=<?php echo $videoPage; ?>" 
                           class="mdui-btn mdui-ripple">
                            <i class="mdui-icon material-icons">chevron_left</i>
                        </a>
                    <?php endif; ?>
                    
                    <!-- 页码 -->
                    <?php
                    $startPage = max(1, $commentPage - 2);
                    $endPage = min($totalCommentPages, $commentPage + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <a href="?comment_page=<?php echo $i; ?>&video_page=<?php echo $videoPage; ?>" 
                           class="mdui-btn mdui-ripple <?php echo $i == $commentPage ? 'mdui-color-pink' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <!-- 下一页 -->
                    <?php if ($commentPage < $totalCommentPages): ?>
                        <a href="?comment_page=<?php echo $commentPage + 1; ?>&video_page=<?php echo $videoPage; ?>" 
                           class="mdui-btn mdui-ripple">
                            <i class="mdui-icon material-icons">chevron_right</i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 夜间模式切换脚本 -->
    <script>
        // 从localStorage获取主题设置
        const currentTheme = localStorage.getItem('theme') || 'light';
        
        // 应用主题
        if (currentTheme === 'dark') {
            document.body.classList.add('mdui-theme-layout-dark');
            document.getElementById('theme-icon').textContent = 'brightness_high';
        }
        
        // 主题切换按钮事件
        document.getElementById('theme-toggle').addEventListener('click', function() {
            const body = document.body;
            const isDark = body.classList.contains('mdui-theme-layout-dark');
            const themeIcon = document.getElementById('theme-icon');
            
            if (isDark) {
                body.classList.remove('mdui-theme-layout-dark');
                themeIcon.textContent = 'brightness_4';
                localStorage.setItem('theme', 'light');
            } else {
                body.classList.add('mdui-theme-layout-dark');
                themeIcon.textContent = 'brightness_high';
                localStorage.setItem('theme', 'dark');
            }
        });
    </script>
</body>
</html>