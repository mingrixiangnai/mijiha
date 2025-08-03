        // 初始化MDUI组件
        document.addEventListener('DOMContentLoaded', function() {
            // 主题切换功能
            const themeToggle = document.getElementById('theme-toggle');
            const themeIcon = document.getElementById('theme-icon');
            const savedTheme = localStorage.getItem('theme') || 'light';// 从localStorage中获取主题设置
            // 初始化主题
            if (savedTheme === 'dark') {
                document.body.classList.add('mdui-theme-layout-dark');
                themeIcon.textContent = 'brightness_7';
            } else {
                document.body.classList.remove('mdui-theme-layout-dark');
                themeIcon.textContent = 'brightness_4';
            }
            // 切换主题
            themeToggle.addEventListener('click', function() {
                document.body.classList.toggle('mdui-theme-layout-dark');
                const isDark = document.body.classList.contains('mdui-theme-layout-dark');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
                themeIcon.textContent = isDark ? 'brightness_7' : 'brightness_4';
            });
        });