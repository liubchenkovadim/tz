<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$pageTitle|default:'Блог на чистом PHP'}</title>
    <meta name="description" content="Современный быстрый блог на чистом PHP 8.1+ со Smarty 5 и MySQL">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-container">
            <a href="/" class="site-logo">
                <span class="logo-text">CleanPHP<strong>Blog</strong></span>
            </a>

            <nav class="site-nav">
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="/" class="nav-link">Главная</a>
                    </li>
                    {if !empty($navCategories)}
                        <li class="nav-item has-dropdown">
                            <span class="nav-link dropdown-toggle">Категории ▾</span>
                            <ul class="dropdown-menu">
                                {foreach $navCategories as $cat}
                                    <li>
                                        <a href="/category/{$cat.slug}">{$cat.title}</a>
                                    </li>
                                {/foreach}
                            </ul>
                        </li>
                    {/if}
                    <li class="nav-item">
                        <a href="/admin" class="nav-link admin-pill">Админка</a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="site-main">
        <div class="container">
            {block name="content"}{/block}
        </div>
    </main>

    <footer class="site-footer">
        <div class="container footer-container">
            <div class="footer-info">
                <p>&copy; {$currentYear} CleanPHP Blog. Разработано на чистом PHP 8.1+, Smarty 5, MySQL и Redis.</p>
            </div>
            <div class="footer-links">
                <a href="/">Главная</a>
                <a href="/admin">Панель управления</a>
            </div>
        </div>
    </footer>
</body>
</html>
