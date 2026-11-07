<article class="post-card">
    <div class="post-card-thumb">
        <a href="/post/{$post.slug}">
            {if !empty($post.image_url)}
                <img src="{$post.image_url}" alt="{$post.title}" loading="lazy" onerror="this.style.display='none';">
            {else}
                <div class="thumb-placeholder">📄</div>
            {/if}
        </a>
    </div>

    <div class="post-card-content">
        <div class="post-card-meta">
            {if !empty($post.categories)}
                <div class="post-categories-list">
                    {foreach $post.categories as $c}
                        <a href="/category/{$c.slug}" class="badge category-badge">{$c.title}</a>
                    {/foreach}
                </div>
            {/if}
            <div class="post-stats">
                <span class="views-stat" title="Просмотров">👁 {$post.views_count}</span>
                <span class="date-stat">📅 {$post.published_at|date_format:"%d.%m.%Y"}</span>
            </div>
        </div>

        <h3 class="post-card-title">
            <a href="/post/{$post.slug}">{$post.title}</a>
        </h3>

        <p class="post-card-desc">
            {$post.description|truncate:140:"..."}
        </p>

        <div class="post-card-footer">
            <a href="/post/{$post.slug}" class="read-more-link">Читать далее &rarr;</a>
        </div>
    </div>
</article>
