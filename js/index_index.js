        // 初始化MDUI组件
        document.addEventListener('DOMContentLoaded', function() {
            // 添加按钮事件
            const addButton = document.getElementById('add-button');
            const addDialog = new mdui.Dialog('#add-dialog');
            addButton.addEventListener('click', function() {
                addDialog.open();
            });
            
            // 取消按钮事件
            document.getElementById('cancel-button').addEventListener('click', function() {
                addDialog.close();
            });
            


            // 表单提交事件
            document.getElementById('add-form').addEventListener('submit', function() {
                addDialog.close();
            });
            


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



            // 搜索按钮事件
            const searchButton = document.getElementById('search-button');
            const searchDialog = new mdui.Dialog('#search-dialog');
            searchButton.addEventListener('click', function() {
                searchDialog.open();
            });
            // 搜索取消按钮事件
            document.getElementById('search-cancel-button').addEventListener('click', function() {
                searchDialog.close();
            });
        });
        
        
        
        
        
        
        // 异步获取视频总数
        document.addEventListener('DOMContentLoaded', function() {
            fetch('index.php?action=get_total')
                .then(response => response.text())
                .then(data => {
                    const totalElement = document.getElementById('total-videos');
                    if (totalElement) {
                        totalElement.textContent = '- 当前共有 ' + data + ' 个视频';
                    }
                })
                .catch(error => {
                    console.error('获取视频总数失败:', error);
                });
        });