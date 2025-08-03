# 配置说明
  把每个.php文件里的“数据库配置”的dbname、username、password改为你自己的数据库信息。<br>
  其中的dbname是数据库名，username是用户名，password是密码。<br>
  <br><br><br>
  数据库需要MySQL，数据库编码为utf8mb4<br>
  php版本为PHP-74<br>
  <br><br><br>
# 创建数据表
```
-- 创建 videos 表
CREATE TABLE `videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bv_id` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `cover_url` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `bv_id` (`bv_id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
  <br><br><br>
```
  -- 创建 comments 表
CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `video_id` int(11) NOT NULL,
  `user_name` varchar(100) NOT NULL DEFAULT '访客',
  `content` text NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `video_id` (`video_id`),
  CONSTRAINT `comments_ibfk_1` 
    FOREIGN KEY (`video_id`) 
    REFERENCES `videos` (`id`) 
    ON DELETE CASCADE 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
