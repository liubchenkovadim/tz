{extends file="admin/layout.tpl"}

{block name="admin_content"}
    <div class="admin-card max-w-700">
        <div class="admin-card-header">
            <h2 class="admin-card-title">
                {if $isEdit}Редактирование категории #{$category.id}{else}Создание новой категории{/if}
            </h2>
            <a href="/admin/categories" class="btn btn-sm btn-outline">&larr; К списку</a>
        </div>

        <div class="admin-card-body">
            {if !empty($error)}
                <div class="alert alert-danger">
                    {$error}
                </div>
            {/if}

            <form method="POST" class="admin-form">
                <div class="form-group">
                    <label for="title" class="form-label">Название категории *</label>
                    <input type="text" id="title" name="title" class="form-control" value="{$category.title|default:''}" required autofocus>
                </div>

                <div class="form-group">
                    <label for="slug" class="form-label">Слаг (URL-идентификатор)</label>
                    <input type="text" id="slug" name="slug" class="form-control" value="{$category.slug|default:''}" placeholder="Оставьте пустым для автогенерации">
                    <small class="form-hint">Только латиница, цифры и дефисы (например: <code>tech-news</code>)</small>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Описание категории</label>
                    <textarea id="description" name="description" class="form-control" rows="4">{$category.description|default:''}</textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        {if $isEdit}Сохранить изменения{else}Создать категорию{/if}
                    </button>
                    <a href="/admin/categories" class="btn btn-outline">Отмена</a>
                </div>
            </form>
        </div>
    </div>
{/block}
