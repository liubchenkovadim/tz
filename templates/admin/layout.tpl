<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$pageTitle|default:'Административная панель'}</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <a href="/admin">⚡ <strong>AdminPanel</strong></a>
            </div>

            <nav class="admin-nav">
                <ul>
                    <li>
                        <a href="/admin" class="admin-nav-link">📊 Дашборд</a>
                    </li>
                    <li>
                        <a href="/admin/categories" class="admin-nav-link">📁 Категории</a>
                    </li>
                    <li>
                        <a href="/admin/posts" class="admin-nav-link">📝 Статьи</a>
                    </li>
                </ul>

                <div class="admin-nav-divider"></div>

                <ul>
                    <li>
                        <a href="/" class="admin-nav-link back-to-site" target="_blank">🌐 Перейти на сайт &rarr;</a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-topbar">
                <h1 class="admin-page-title">{$pageTitle|default:'Панель управления'}</h1>
                <div class="admin-topbar-actions">
                    <a href="/admin/posts/create" class="btn btn-sm btn-primary">+ Новая статья</a>
                    <a href="/admin/categories/create" class="btn btn-sm btn-outline">+ Новая категория</a>
                </div>
            </header>

            <div class="admin-content">
                {block name="admin_content"}{/block}
            </div>
        </main>
    </div>
</body>
</html>
