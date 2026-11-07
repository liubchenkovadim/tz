# План реализации блога (Чистый PHP 8.1+, Smarty, MySQL, Redis, Админка)

## 1. Архитектура и стек технологий

```
├── .docker/                   # Конфигурации сервисов (Nginx, PHP)
├── bin/                       # Консольные скрипты (сидер)
│   └── seed.php               # Скрипт наполнения базы данных
├── config/                    # Конфигурационные файлы
│   └── database.php           # Параметры подключения к MySQL
├── database/                  # SQL-дампы и схема БД
│   └── schema.sql             # Таблицы, индексы и внешние ключи
├── public/                    # Web-root (публичная директория)
│   ├── assets/                # Скомпилированный CSS, изображения
│   │   └── css/style.css
│   └── index.php              # Front Controller (минимальная точка входа)
├── routes/                    # Маршрутизация приложения
│   └── web.php                # Регистрация всех маршрутов
├── src/                       # Исходный код приложения (PSR-4: App\)
│   ├── Controllers/           # Контроллеры публичной части и админки
│   │   ├── Admin/             # DashboardController, CategoryAdminController, PostAdminController
│   │   ├── HomeController.php
│   │   ├── CategoryController.php
│   │   └── PostController.php
│   ├── Core/                  # Базовое ядро
│   │   ├── App.php            # Главный жизненный цикл приложения
│   │   ├── Cache.php          # Обертка над Redis (Predis)
│   │   ├── Database.php       # Фабрика PDO с Prepared Statements
│   │   ├── Request.php        # HTTP-запрос (GET, POST, query params)
│   │   ├── Response.php       # HTTP-ответ и редиректы
│   │   ├── Router.php         # Регулярный HTTP-маршрутизатор
│   │   └── View.php           # Адаптер шаблонизатора Smarty 5
│   ├── Enums/                 # PHP 8.1 Backed Enums
│   │   └── PostSort.php       # Безопасные опции сортировки
│   └── Repositories/          # Слой работы с БД (Prepared Statements)
│       ├── CategoryRepository.php
│       └── PostRepository.php
├── templates/                 # Шаблоны Smarty (.tpl)
│   ├── admin/                 # Шаблоны админки (дашборд с Топ-10, формы, списки)
│   ├── components/            # Компоненты (карточка поста, пагинация)
│   ├── layouts/               # Базовый макет сайта (main.tpl)
│   ├── home.tpl               # Главная страница
│   ├── category.tpl           # Страница категории
│   ├── post.tpl               # Страница статьи
│   └── 404.tpl                # Страница 404 ошибки
├── scss/                      # Исходные файлы стилей SCSS
│   ├── _variables.scss
│   ├── _reset.scss
│   ├── _components.scss
│   ├── _admin.scss
│   └── main.scss
├── var/                       # Кэш и скомпилированные шаблоны Smarty
│   └── templates_c/
├── docker-compose.yml         # Окружение Docker (Nginx, PHP-FPM, MySQL, Redis)
├── composer.json              # Зависимости PHP (Smarty, Predis, Faker)
├── package.json               # Сборка SCSS (sass CLI)
└── README.md                  # Полная инструкция по запуску
```

---

## 2. Структура базы данных (Many-to-Many: одна или несколько категорий)

### 1. Таблица `categories`
- `id` (INT UNSIGNED AUTO_INCREMENT PRIMARY KEY)
- `slug` (VARCHAR(128) UNIQUE NOT NULL)
- `title` (VARCHAR(255) NOT NULL)
- `description` (TEXT NULL)
- `created_at` (DATETIME DEFAULT CURRENT_TIMESTAMP)

### 2. Таблица `posts`
- `id` (INT UNSIGNED AUTO_INCREMENT PRIMARY KEY)
- `slug` (VARCHAR(128) UNIQUE NOT NULL)
- `title` (VARCHAR(255) NOT NULL)
- `description` (TEXT NOT NULL) — краткий анонс статьи
- `content` (LONGTEXT NOT NULL) — полный текст статьи
- `image_url` (VARCHAR(255) NULL) — URL изображения
- `views_count` (INT UNSIGNED DEFAULT 0 NOT NULL) — счетчик просмотров
- `published_at` (DATETIME NOT NULL) — дата публикации
- `created_at` (DATETIME DEFAULT CURRENT_TIMESTAMP)

### 3. Таблица связей `post_categories` (Many-to-Many)
- `post_id` (INT UNSIGNED NOT NULL)
- `category_id` (INT UNSIGNED NOT NULL)
- `PRIMARY KEY (post_id, category_id)`
- `FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE`
- `FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE`
- `KEY idx_category_post (category_id, post_id)`

**Индексы для оптимизации запросов:**
- `idx_posts_views (views_count DESC)`
- `idx_posts_published (published_at DESC)`
- `idx_posts_slug (slug)`
- `idx_category_post (category_id, post_id)`

---

## 3. Обязательные страницы и бизнес-логика

### 1. Главная страница (`/`)
- Выборка только тех категорий, в которых есть хотя бы одна статья (`SELECT ... WHERE EXISTS`).
- Для каждой категории отображение **3 последних статей** (по дате публикации `published_at DESC`).
- Кнопка **«Все статьи»** для перехода в конкретную категорию.

### 2. Страница категории (`/category/{slug}`)
- Вывод названия и описания категории.
- **Сортировка статей**:
  - По дате публикации (сначала новые / сначала старые)
  - По количеству просмотров (самые популярные / наименее популярные)
  - Валидация параметров через PHP 8.1 Enum `PostSort` (исключение SQL Injection).
- **Пагинация**:
  - По 6 статей на страницу с сохранением выбранной сортировки (`?sort=...&page=...`).

### 3. Страница статьи (`/post/{slug}`)
- Полная информация: изображение, название, описание, текст, бейджи категорий, дата публикации, счетчик просмотров.
- **Инкремент просмотров**: атомарное обновление `views_count = views_count + 1`.
- **Блок из 3 похожих статей**: статьи с наибольшим количеством общих категорий с текущей статьей.

---

## 4. Дополнительный функционал

### 1. Сидер (`bin/seed.php`)
- CLI-скрипт на чистом PHP с Faker (`ru_RU`).
- Генерирует 6 категорий и 30 статей со связями Many-to-Many, просмотрами и датами.

### 2. Кэширование в Redis (`src/Core/Cache.php`)
- Кэширование секций главной страницы и списка категорий.
- Автоматическая инвалидация кэша при добавлении, редактировании или удалении контента в админке.

### 3. Панель администратора (`/admin`)
- **Дашборд**: статистика (статьи, категории, просмотры) + **Топ-10 самых просматриваемых статей**.
- **Категории**: список, создание, редактирование, удаление.
- **Статьи**: список с пагинацией, создание с выбором нескольких категорий (чекбоксы), редактирование, удаление.
