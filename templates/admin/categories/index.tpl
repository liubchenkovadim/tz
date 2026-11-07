{extends file="admin/layout.tpl"}

{block name="admin_content"}
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title">Список категорий</h2>
                <p class="admin-card-subtitle">Все доступные категории блога и количество статей в них</p>
            </div>
            <a href="/admin/categories/create" class="btn btn-sm btn-primary">+ Добавить категорию</a>
        </div>

        <div class="admin-card-body">
            {if empty($categories)}
                <p class="text-muted">Категорий пока не создано.</p>
            {else}
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th>Название</th>
                                <th>Слаг (URL)</th>
                                <th>Описание</th>
                                <th style="width: 120px; text-align: center;">Статей</th>
                                <th style="width: 140px; text-align: center;">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $categories as $cat}
                                <tr>
                                    <td>#{$cat.id}</td>
                                    <td>
                                        <strong>
                                            <a href="/category/{$cat.slug}" target="_blank">{$cat.title}</a>
                                        </strong>
                                    </td>
                                    <td><code>{$cat.slug}</code></td>
                                    <td>{$cat.description|truncate:60:"..."|default:'—'}</td>
                                    <td style="text-align: center;">
                                        <span class="badge">{$cat.posts_count|default:0}</span>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="table-actions">
                                            <a href="/admin/categories/edit/{$cat.id}" class="action-btn edit-btn" title="Редактировать">✏️</a>
                                            <a href="/admin/categories/delete/{$cat.id}" class="action-btn delete-btn" title="Удалить" onclick="return confirm('Вы уверены, что хотите удалить категорию «{$cat.title|escape:'javascript'}»?');">🗑️</a>
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
