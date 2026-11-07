{extends file="admin/layout.tpl"}

{block name="admin_content"}
    <!-- Overview stats cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📝</div>
            <div class="stat-info">
                <span class="stat-label">Всего статей</span>
                <strong class="stat-value">{$totalPosts}</strong>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📁</div>
            <div class="stat-info">
                <span class="stat-label">Всего категорий</span>
                <strong class="stat-value">{$totalCategories}</strong>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">👁️</div>
            <div class="stat-info">
                <span class="stat-label">Сумма просмотров</span>
                <strong class="stat-value">{$totalViews|number_format:0:'':' '}</strong>
            </div>
        </div>
    </div>

    <!-- Top 10 Most Viewed Posts Table -->
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title">🔥 Топ-10 самых популярных статей по просмотрам</h2>
                <p class="admin-card-subtitle">Рейтинг статей по максимальному количеству просмотров</p>
            </div>
            <a href="/admin/posts" class="btn btn-sm btn-outline">Все статьи &rarr;</a>
        </div>

        <div class="admin-card-body">
            {if empty($topPosts)}
                <p class="text-muted">Статей пока нет.</p>
            {else}
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Статья</th>
                                <th>Категории</th>
                                <th style="width: 130px; text-align: right;">Просмотров</th>
                                <th style="width: 140px;">Дата</th>
                                <th style="width: 130px; text-align: center;">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $topPosts as $index => $post}
                                <tr>
                                    <td>
                                        <span class="rank-badge rank-{$index + 1}">{$index + 1}</span>
                                    </td>
                                    <td>
                                        <div class="post-title-cell">
                                            {if !empty($post.image_url)}
                                                <img src="{$post.image_url}" alt="" class="table-thumb">
                                            {/if}
                                            <div>
                                                <strong>
                                                    <a href="/post/{$post.slug}" target="_blank" class="table-post-link">
                                                        {$post.title}
                                                    </a>
                                                </strong>
                                                <div class="text-muted table-slug">/{$post.slug}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        {if !empty($post.categories)}
                                            <div class="badge-group">
                                                {foreach $post.categories as $c}
                                                    <span class="badge badge-sm">{$c.title}</span>
                                                {/foreach}
                                            </div>
                                        {else}
                                            <span class="text-muted">—</span>
                                        {/if}
                                    </td>
                                    <td style="text-align: right;">
                                        <span class="badge badge-views">👁 {$post.views_count|number_format:0:'':' '}</span>
                                    </td>
                                    <td>
                                        {$post.published_at|date_format:"%d.%m.%Y"}
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="table-actions">
                                            <a href="/admin/posts/edit/{$post.id}" class="action-btn edit-btn" title="Редактировать">✏️</a>
                                            <a href="/post/{$post.slug}" target="_blank" class="action-btn view-btn" title="Посмотреть на сайте">👁️</a>
                                        </div>
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            {/if}
        </div>
    </div>
{/block}
