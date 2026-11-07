{extends file="layouts/main.tpl"}

{block name="content"}
    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Чистый PHP Блог</h1>
            <p class="hero-subtitle">
                Современная архитектура без сторонних фреймворков, шаблонизатор Smarty 5, реляционная база данных MySQL и кэширование в Redis.
            </p>
        </div>
    </section>

    {if empty($sections)}
        <div class="empty-state">
            <p>Статей пока нет. Запустите сидер <code>php bin/seed.php</code> для наполнения базы данных.</p>
        </div>
    {else}
        <div class="home-sections">
            {foreach $sections as $cat}
                <section class="category-section">
                    <div class="section-header">
                        <div class="section-title-wrap">
                            <h2 class="section-title">
                                <a href="/category/{$cat.slug}">{$cat.title}</a>
                            </h2>
                            {if !empty($cat.description)}
                                <p class="section-desc">{$cat.description}</p>
                            {/if}
                        </div>
                        <a href="/category/{$cat.slug}" class="btn btn-outline btn-all-posts">
                            Все статьи ({$cat.posts_count}) &rarr;
                        </a>
                    </div>

                    <div class="posts-grid">
                        {foreach $cat.posts as $post}
                            {include file="components/post_card.tpl" post=$post}
                        {/foreach}
                    </div>
                </section>
            {/foreach}
        </div>
    {/if}
{/block}
