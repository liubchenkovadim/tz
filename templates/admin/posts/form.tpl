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

            <form method="POST" class="admin-form" id="postForm">
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
                        <input type="url" id="image_url" name="image_url" class="form-control" value="{$post.image_url|default:''}" placeholder="https://picsum.photos/seed/my-slug/800/450">
                    </div>
                </div>

                {* Interactive Category Picker with Search and Tags *}
                <div class="form-group category-picker-group">
                    <label class="form-label">Категории статьи * (выберите одну или несколько)</label>
                    
                    {* Tags Container: Selected categories appear above the select with a remove button *}
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

                    {* Search Box + Dropdown Items *}
                    <div class="category-search-picker">
                        <div class="search-input-wrapper">
                            <span class="search-icon">🔍</span>
                            <input type="text" id="categorySearchInput" class="form-control category-search-input" placeholder="Поиск категории по названию..." autocomplete="off">
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
                                Категории не найдены
                            </div>
                        </div>
                    </div>
                    <small class="form-hint">Кликните по категории в списке для добавления. Выбранные отображаются выше в виде плашек с крестиком для удаления.</small>
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
