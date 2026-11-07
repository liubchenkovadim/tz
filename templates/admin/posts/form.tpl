{extends file="admin/layout.tpl"}

{block name="admin_content"}
    {if !empty($error)}
        <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
            {$error}
        </div>
    {/if}

    <form method="POST" class="admin-form" id="postForm">
        <div class="editor-layout">
            {* Left column: Main content *}
            <div class="editor-main">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">
                            {if $isEdit}Редактирование статьи #{$post.id}{else}Новая статья{/if}
                        </h2>
                        <a href="/admin/posts" class="btn btn-sm btn-outline">&larr; К списку</a>
                    </div>

                    <div class="admin-card-body">
                        <div class="form-group">
                            <label for="title" class="form-label" style="font-size: 1rem;">Заголовок статьи *</label>
                            <input type="text" id="title" name="title" class="form-control" style="font-size: 1.15rem; font-weight: 600; padding: 0.75rem 1rem;" value="{$post.title|default:''}" placeholder="Введите заголовок..." required autofocus>
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Краткое описание / анонс *</label>
                            <textarea id="description" name="description" class="form-control" rows="3" placeholder="Краткое содержание статьи для списка..." required>{$post.description|default:''}</textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="content" class="form-label">Полный текст статьи (поддерживает HTML) *</label>
                            <textarea id="content" name="content" class="form-control" rows="18" placeholder="Полный текст публикации..." required>{$post.content|default:''}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {* Right column: Meta settings, Categories & Image *}
            <div class="editor-sidebar">
                {* Card 1: Publish actions *}
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title" style="font-size: 1.05rem;">⚙️ Публикация</h3>
                    </div>
                    <div class="admin-card-body">
                        <div class="form-group">
                            <label for="published_at" class="form-label">Дата и время публикации</label>
                            <input type="datetime-local" id="published_at" name="published_at" class="form-control" value="{$post.published_at_input|default:''}">
                        </div>

                        <div class="form-group">
                            <label for="views_count" class="form-label">Количество просмотров</label>
                            <input type="number" id="views_count" name="views_count" class="form-control" value="{$post.views_count|default:0}" min="0">
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1.25rem;">
                            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem;">
                                {if $isEdit}💾 Сохранить изменения{else}🚀 Опубликовать статью{/if}
                            </button>
                            <a href="/admin/posts" class="btn btn-outline" style="width: 100%; text-align: center;">
                                Отмена
                            </a>
                        </div>
                    </div>
                </div>

                {* Card 2: Interactive Categories Picker *}
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title" style="font-size: 1.05rem;">📁 Категории статьи *</h3>
                    </div>
                    <div class="admin-card-body">
                        <div class="category-picker-group">
                            {* Tags container: Selected categories appear above the select with a remove button *}
                            <div id="selectedCategoriesTags" class="selected-categories-tags">
                                {foreach $categories as $cat}
                                    {if in_array($cat.id, $selectedCategories)}
                                        <div class="category-tag-badge" id="cat-badge-{$cat.id}" data-id="{$cat.id}">
                                            <span class="badge-title">{$cat.title}</span>
                                            <button type="button" class="tag-remove-btn" onclick="removeCategory({$cat.id})" title="Удалить">&times;</button>
                                            <input type="hidden" name="category_ids[]" value="{$cat.id}">
                                        </div>
                                    {/if}
                                {/foreach}
                            </div>

                            {* Search input + dropdown *}
                            <div class="category-search-picker">
                                <div class="search-input-wrapper">
                                    <span class="search-icon">🔍</span>
                                    <input type="text" id="categorySearchInput" class="form-control category-search-input" placeholder="Поиск категории..." autocomplete="off">
                                </div>

                                <div id="categoryDropdownList" class="category-dropdown-list">
                                    {foreach $categories as $cat}
                                        <div class="category-dropdown-item {if in_array($cat.id, $selectedCategories)}is-selected{/if}"
                                             id="cat-item-{$cat.id}"
                                             data-id="{$cat.id}"
                                             data-title="{$cat.title|escape:'html'}"
                                             onclick="toggleCategory({$cat.id}, '{$cat.title|escape:'javascript'}')">
                                            <span class="cat-item-title">{$cat.title}</span>
                                            <span class="cat-item-check">✓ Добавлено</span>
                                        </div>
                                    {/foreach}
                                    <div id="noCategoriesFound" class="category-dropdown-empty" style="display: none;">
                                        Ничего не найдено
                                    </div>
                                </div>
                            </div>
                            <small class="form-hint" style="margin-top: 0.5rem;">Кликните по категории для добавления. Выбранные отображаются плашками сверху.</small>
                        </div>
                    </div>
                </div>

                {* Card 3: Slug & Image *}
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title" style="font-size: 1.05rem;">🖼️ URL и Обложка</h3>
                    </div>
                    <div class="admin-card-body">
                        <div class="form-group">
                            <label for="slug" class="form-label">Слаг статьи (URL)</label>
                            <input type="text" id="slug" name="slug" class="form-control" value="{$post.slug|default:''}" placeholder="Оставьте пустым для автогенерации">
                            <small class="form-hint">Например: <code>my-first-post</code></small>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="image_url" class="form-label">URL изображения</label>
                            <input type="url" id="image_url" name="image_url" class="form-control" value="{$post.image_url|default:''}" placeholder="https://picsum.photos/seed/.../800/450" oninput="updateImagePreview(this.value)">
                            
                            <div class="image-preview-wrapper" id="imagePreviewBox">
                                {if !empty($post.image_url)}
                                    <img src="{$post.image_url}" alt="Превью обложки" id="previewImg" onerror="this.style.display='none'">
                                {else}
                                    <div class="image-preview-empty" id="emptyPreviewText">Превью появится здесь после ввода URL</div>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {literal}
    <script>
        function toggleCategory(id, title) {
            const existingBadge = document.getElementById('cat-badge-' + id);
            if (existingBadge) {
                removeCategory(id);
                return;
            }

            // Create badge element
            const container = document.getElementById('selectedCategoriesTags');
            const badge = document.createElement('div');
            badge.className = 'category-tag-badge';
            badge.id = 'cat-badge-' + id;
            badge.setAttribute('data-id', id);

            const titleSpan = document.createElement('span');
            titleSpan.className = 'badge-title';
            titleSpan.textContent = title;

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'tag-remove-btn';
            removeBtn.innerHTML = '&times;';
            removeBtn.title = 'Удалить';
            removeBtn.onclick = function() { removeCategory(id); };

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'category_ids[]';
            hiddenInput.value = id;

            badge.appendChild(titleSpan);
            badge.appendChild(removeBtn);
            badge.appendChild(hiddenInput);

            container.appendChild(badge);

            // Mark in dropdown
            const item = document.getElementById('cat-item-' + id);
            if (item) {
                item.classList.add('is-selected');
            }
        }

        function removeCategory(id) {
            const badge = document.getElementById('cat-badge-' + id);
            if (badge) {
                badge.remove();
            }

            const item = document.getElementById('cat-item-' + id);
            if (item) {
                item.classList.remove('is-selected');
            }
        }

        function updateImagePreview(url) {
            const box = document.getElementById('imagePreviewBox');
            if (!url || !url.trim()) {
                box.innerHTML = '<div class="image-preview-empty">Превью появится здесь после ввода URL</div>';
                return;
            }

            box.innerHTML = '<img src="' + url.replace(/"/g, '&quot;') + '" alt="Превью обложки" onerror="this.parentElement.innerHTML=\'<div class=\\\'image-preview-empty\\\'>Не удалось загрузить изображение</div>\'">';
        }

        // Live search filter in category list
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('categorySearchInput');
            const dropdownList = document.getElementById('categoryDropdownList');
            const emptyMsg = document.getElementById('noCategoriesFound');
            const items = dropdownList.querySelectorAll('.category-dropdown-item');

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const query = this.value.trim().toLowerCase();
                    let visibleCount = 0;

                    items.forEach(function(item) {
                        const title = (item.getAttribute('data-title') || '').toLowerCase();
                        if (query === '' || title.includes(query)) {
                            item.style.display = 'flex';
                            visibleCount++;
                        } else {
                            item.style.display = 'none';
                        }
                    });

                    if (emptyMsg) {
                        emptyMsg.style.display = visibleCount === 0 ? 'block' : 'none';
                    }
                });
            }
        });
    </script>
    {/literal}
{/block}
