{extends file="layouts/main.tpl"}

{block name="content"}
    <article class="single-post">
        <nav class="breadcrumbs" aria-label="Хлебные крошки">
            <a href="/">Главная</a>
            <span class="sep">/</span>
            {if !empty($post.categories)}
                <a href="/category/{$post.categories[0].slug}">{$post.categories[0].title}</a>
                <span class="sep">/</span>
            {/if}
            <span class="current">{$post.title|truncate:40:"..."}</span>
        </nav>

        <header class="single-post-header">
            <div class="single-post-categories">
                {foreach $post.categories as $c}
                    <a href="/category/{$c.slug}" class="badge category-badge">{$c.title}</a>
                {/foreach}
            </div>

            <h1 class="single-post-title">{$post.title}</h1>

            <div class="single-post-meta">
                <span class="meta-item">📅 Опубликовано: {$post.published_at|date_format:"%d.%m.%Y в %H:%M"}</span>
                <span class="meta-item views-meta">👁 Просмотров: <strong>{$post.views_count}</strong></span>
            </div>

            <p class="single-post-lead">
                {$post.description}
            </p>
        </header>

        {if !empty($post.image_url)}
            <div class="single-post-banner">
                <img src="{$post.image_url}" alt="{$post.title}">
            </div>
        {/if}

        <div class="single-post-body content-typography">
            {$post.content nofilter}
        </div>
    </article>

    {* Similar posts section *}
    {if !empty($similarPosts)}
        <section class="similar-posts-section">
            <div class="similar-header">
                <h2 class="similar-title">Похожие статьи</h2>
                <p class="similar-subtitle">Материалы со схожей тематикой, которые могут вас заинтересовать</p>
            </div>

            <div class="posts-grid similar-grid">
                {foreach $similarPosts as $simPost}
                    {include file="components/post_card.tpl" post=$simPost}
                {/foreach}
            </div>
        </section>
    {/if}
{/block}
