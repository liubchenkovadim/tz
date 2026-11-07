{extends file="layouts/main.tpl"}

{block name="content"}
    <div class="error-page">
        <div class="error-code">404</div>
        <h1 class="error-title">{$pageTitle|default:'Страница не найдена'}</h1>
        <p class="error-desc">
            {$message|default:'Запрашиваемой страницы не существует, либо она была перемещена или удалена.'}
        </p>
        <div class="error-actions">
            <a href="/" class="btn btn-primary">&larr; Вернуться на главную</a>
        </div>
    </div>
{/block}
