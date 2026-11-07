{extends file="layouts/main.tpl"}

{block name="content"}
    <div class="category-header">
        <nav class="breadcrumbs" aria-label="Хлебные крошки">
            <a href="/">Главная</a>
            <span class="sep">/</span>
            <span class="current">{$category.title}</span>
        </nav>

        <h1 class="category-page-title">{$category.title}</h1>
        {if !empty($category.description)}
            <p class="category-page-desc">{$category.description}</p>
        {/if}
    </div>

    <div class="category-toolbar">
        <div class="toolbar-info">
            Всего статей: <strong>{$pagination.totalPosts}</strong>
        </div>

        <div class="toolbar-sort">
            <form method="GET" action="/category/{$category.slug}" class="sort-form">
                <label for="sort-select" class="sort-label">Сортировка:</label>
                <select name="sort" id="sort-select" class="sort-select" onchange="this.form.submit()">
                    {foreach $sortOptions as $opt}
                        <option value="{$opt.value}" {if $currentSort == $opt.value}selected{/if}>
                            {$opt.label}
                        </option>
                    {/foreach}
                </select>
                <noscript>
                    <button type="submit" class="btn btn-sm">Применить</button>
                </noscript>
            </form>
        </div>
    </div>

    {if empty($posts)}
        <div class="empty-state">
            <p>В этой категории пока нет опубликованных статей.</p>
        </div>
    {else}
        <div class="posts-grid category-posts-grid">
            {foreach $posts as $post}
                {include file="components/post_card.tpl" post=$post}
            {/foreach}
        </div>

        {include file="components/pagination.tpl" pagination=$pagination}
    {/if}
{/block}
