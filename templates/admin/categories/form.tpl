{extends file="admin/layout.tpl"}

{block name="admin_content"}
    {if !empty($error)}
        <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
            {$error}
        </div>
    {/if}

    <form method="POST" class="admin-form">
        <div class="editor-layout">
            {* Left column: Main fields *}
            <div class="editor-main">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">
                            {if $isEdit}Редактирование категории #{$category.id}{else}Новая категория{/if}
                        </h2>
                        <a href="/admin/categories" class="btn btn-sm btn-outline">&larr; К списку категорий</a>
                    </div>

                    <div class="admin-card-body">
                        <div class="form-group">
                            <label for="title" class="form-label" style="font-size: 1rem;">Название категории *</label>
                            <input type="text" id="title" name="title" class="form-control" style="font-size: 1.15rem; font-weight: 600; padding: 0.75rem 1rem;" value="{$category.title|default:''}" placeholder="Например: Искусственный интеллект и ML" required autofocus>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="description" class="form-label">Описание категории</label>
                            <textarea id="description" name="description" class="form-control" rows="8" placeholder="Краткое описание тематики статей этой категории...">{$category.description|default:''}</textarea>
                            <small class="form-hint" style="margin-top: 0.5rem;">Описание отображается в шапке страницы категории на публичном сайте.</small>
                        </div>
                    </div>
                </div>
            </div>

            {* Right column: Meta & Settings Sidebar *}
            <div class="editor-sidebar">
                {* Card 1: Save action *}
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title" style="font-size: 1.05rem;">⚙️ Сохранение</h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem;">
                                {if $isEdit}💾 Сохранить изменения{else}📁 Создать категорию{/if}
                            </button>
                            <a href="/admin/categories" class="btn btn-outline" style="width: 100%; text-align: center;">
                                Отмена
                            </a>
                        </div>
                    </div>
                </div>

                {* Card 2: URL Slug *}
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title" style="font-size: 1.05rem;">🔗 Настройки URL</h3>
                    </div>
                    <div class="admin-card-body">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="slug" class="form-label">Слаг категории</label>
                            <input type="text" id="slug" name="slug" class="form-control" value="{$category.slug|default:''}" placeholder="Оставьте пустым для автогенерации">
                            <small class="form-hint">Используется в URL: <code>/category/<strong>{if !empty($category.slug)}{$category.slug}{else}vash-slug{/if}</strong></code></small>
                        </div>
                    </div>
                </div>

                {* Card 3: Stats (if edit mode) *}
                {if $isEdit}
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title" style="font-size: 1.05rem;">📊 Справка</h3>
                        </div>
                        <div class="admin-card-body" style="font-size: 0.9rem;">
                            <p style="margin-bottom: 0.5rem;">
                                <strong>ID категории:</strong> #{$category.id}
                            </p>
                            <p style="margin-bottom: 0.5rem;">
                                <strong>Статей в категории:</strong> <span class="badge">{$postsCount|default:0}</span>
                            </p>
                            {if !empty($category.created_at)}
                                <p style="margin-bottom: 1rem;">
                                    <strong>Создана:</strong> {$category.created_at|date_format:"%d.%m.%Y в %H:%M"}
                                </p>
                            {/if}
                            <a href="/category/{$category.slug}" target="_blank" class="btn btn-sm btn-outline" style="width: 100%; text-align: center;">
                                🌐 Открыть категорию на сайте &rarr;
                            </a>
                        </div>
                    </div>
                {/if}
            </div>
        </div>
    </form>
{/block}
