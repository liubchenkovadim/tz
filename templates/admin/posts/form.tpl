{extends file="admin/layout.tpl"}

{block name="admin_content"}
    <div class="admin-card max-w-850">
        <div class="admin-card-header">
            <h2 class="admin-card-title">
                {if $isEdit}Редактирование статьи #{$post.id}{else}Создание новой статьи{/if}
            </h2>
            <a href="/admin/posts" class="btn btn-sm btn-outline">&larr; К списку статей</a>
        </div>

        <div class="admin-card-body">
            {if !empty($error)}
                <div class="alert alert-danger">
                    {$error}
                </div>
            {/if}

            <form method="POST" class="admin-form">
                <div class="form-group">
                    <label for="title" class="form-label">Название статьи *</label>
                    <input type="text" id="title" name="title" class="form-control" value="{$post.title|default:''}" required autofocus>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="slug" class="form-label">Слаг (URL-идентификатор)</label>
                        <input type="text" id="slug" name="slug" class="form-control" value="{$post.slug|default:''}" placeholder="Оставьте пустым для автогенерации">
                    </div>

                    <div class="form-group col-6">
                        <label for="image_url" class="form-label">URL изображения</label>
                        <input type="url" id="image_url" name="image_url" class="form-control" value="{$post.image_url|default:''}" placeholder="https://picsum.photos/id/101/800/450">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Категории статьи * (одна или несколько)</label>
                    <div class="categories-checkbox-grid">
                        {foreach $categories as $cat}
                            <label class="checkbox-label">
                                <input type="checkbox" name="category_ids[]" value="{$cat.id}" 
                                    {if in_array($cat.id, $selectedCategories)}checked{/if}>
                                <span>{$cat.title}</span>
                            </label>
                        {/foreach}
                    </div>
                    {if empty($categories)}
                        <small class="text-danger">Категорий пока нет! <a href="/admin/categories/create">Создайте категорию</a> сначала.</small>
                    {/if}
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Краткое описание / анонс *</label>
                    <textarea id="description" name="description" class="form-control" rows="3" required>{$post.description|default:''}</textarea>
                </div>

                <div class="form-group">
                    <label for="content" class="form-label">Полный текст статьи (поддерживает HTML) *</label>
                    <textarea id="content" name="content" class="form-control" rows="10" required>{$post.content|default:''}</textarea>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="views_count" class="form-label">Количество просмотров</label>
                        <input type="number" id="views_count" name="views_count" class="form-control" value="{$post.views_count|default:0}" min="0">
                    </div>

                    <div class="form-group col-6">
                        <label for="published_at" class="form-label">Дата и время публикации</label>
                        <input type="datetime-local" id="published_at" name="published_at" class="form-control" value="{$post.published_at_input|default:''}">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        {if $isEdit}Сохранить изменения{else}Опубликовать статью{/if}
                    </button>
                    <a href="/admin/posts" class="btn btn-outline">Отмена</a>
                </div>
            </form>
        </div>
    </div>
{/block}
