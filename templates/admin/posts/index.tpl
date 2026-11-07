{extends file="admin/layout.tpl"}

{block name="admin_content"}
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title">Список статей ({$totalPosts})</h2>
                <p class="admin-card-subtitle">Все статьи блога с возможностью быстрого редактирования и удаления</p>
            </div>
            <a href="/admin/posts/create" class="btn btn-sm btn-primary">+ Добавить статью</a>
        </div>

        <div class="admin-card-body">
            {if empty($posts)}
                <p class="text-muted">Статей пока нет.</p>
            {else}
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Статья</th>
                                <th>Категории</th>
                                <th style="width: 110px; text-align: right;">Просмотров</th>
                                <th style="width: 120px;">Дата</th>
                                <th style="width: 120px; text-align: center;">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $posts as $post}
                                <tr>
                                    <td>#{$post.id}</td>
                                    <td>
                                        <div class="post-title-cell">
                                            {if !empty($post.image_url)}
                                                <img src="{$post.image_url}" alt="" class="table-thumb">
                                            {/if}
                                            <div>
                                                <strong>
                                                    <a href="/post/{$post.slug}" target="_blank">{$post.title}</a>
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
                                            <span class="text-muted">Без категории</span>
                                        {/if}
                                    </td>
                                    <td style="text-align: right;">
                                        <span class="badge badge-views">👁 {$post.views_count}</span>
                                    </td>
                                    <td>
                                        {$post.published_at|date_format:"%d.%m.%Y"}
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="table-actions">
                                            <a href="/admin/posts/edit/{$post.id}" class="action-btn edit-btn" title="Редактировать">✏️</a>
                                            <a href="/admin/posts/delete/{$post.id}" class="action-btn delete-btn" title="Удалить" onclick="return confirm('Удалить статью «{$post.title|escape:'javascript'}»?');">🗑️</a>
                                        </div>
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>

                {if $totalPages > 1}
                    <div class="admin-pagination">
                        {if $currentPage > 1}
                            <a href="/admin/posts?page={$currentPage - 1}" class="btn btn-sm btn-outline">&laquo; Предыдущая</a>
                        {/if}

                        <span class="page-indicator">Страница {$currentPage} из {$totalPages}</span>

                        {if $currentPage < $totalPages}
                            <a href="/admin/posts?page={$currentPage + 1}" class="btn btn-sm btn-outline">Следующая &raquo;</a>
                        {/if}
                    </div>
                {/if}
            {/if}
        </div>
    </div>
{/block}
