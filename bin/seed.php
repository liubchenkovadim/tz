<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;
use Faker\Factory;

echo "=== Запуск сидера базы данных ===\n";

try {
    $db = Database::getConnection();

    // Disable foreign key checks for clean truncation
    $db->exec('SET FOREIGN_KEY_CHECKS = 0');
    $db->exec('TRUNCATE TABLE post_categories');
    $db->exec('TRUNCATE TABLE posts');
    $db->exec('TRUNCATE TABLE categories');
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');

    echo "[1/4] Таблицы очищены.\n";

    // 1. Seed Categories
    $categoriesData = [
        [
            'title'       => 'Технологии и разработка',
            'slug'        => 'tech-development',
            'description' => 'Все о современных языках программирования, архитектуре ПО, базах данных и лучших практиках разработки.',
        ],
        [
            'title'       => 'Искусственный интеллект и ML',
            'slug'        => 'ai-machine-learning',
            'description' => 'Новости нейросетей, машинное обучение, практические кейсы внедрения LLM и генеративного AI в бизнес.',
        ],
        [
            'title'       => 'Веб-дизайн и UX/UI',
            'slug'        => 'design-ux-ui',
            'description' => 'Проектирование интерфейсов, юзабилити-исследования, типографика, дизайн-системы и визуальные тренды.',
        ],
        [
            'title'       => 'Кибербезопасность и DevOps',
            'slug'        => 'cybersecurity-devops',
            'description' => 'Защита информационных систем, безопасность кода, облачная инфраструктура, CI/CD, Kubernetes и Docker.',
        ],
        [
            'title'       => 'Карьера и менеджмент',
            'slug'        => 'career-management',
            'description' => 'Советы для разработчиков: рост от Junior до Lead, прохождение собеседований, управление IT-командами.',
        ],
        [
            'title'       => 'Мобильная разработка',
            'slug'        => 'mobile-apps',
            'description' => 'Мир разработки под iOS и Android, кроссплатформенные фреймворки, оптимизация производительности приложений.',
        ],
    ];

    $catStmt = $db->prepare('INSERT INTO categories (title, slug, description) VALUES (:title, :slug, :description)');
    $categoryIds = [];

    foreach ($categoriesData as $cat) {
        $catStmt->execute($cat);
        $categoryIds[] = (int)$db->lastInsertId();
    }

    echo "[2/4] Создано " . count($categoryIds) . " категорий.\n";

    // 2. Seed Posts with ru_RU Faker
    $faker = Factory::create('ru_RU');

    $postTitles = [
        'Чистый PHP в 2026 году: Почему микросервисы возвращаются к истокам',
        'Архитектура баз данных: Как правильно проектировать связи Many-to-Many',
        'Обзор возможностей Smarty 5: Новый взгляд на классический шаблонизатор',
        'Как оптимизировать индексы в MySQL для миллионов просмотров в сутки',
        'Docker для PHP-разработчика: Полный гайд от настройки Nginx до PHP-FPM',
        'Современный SCSS: Переменные, миксины и модульная структура больших проектов',
        'Как защитить свой веб-сайт от SQL-инъекций и XSS-уязвимостей',
        'Внедрение локальных моделей искусственного интеллекта в веб-сервисы',
        'Принципы SOLID на практических примерах из реального кода',
        'Рефакторинг легаси-кода: Стратегии безопасного обновления систем',
        'Создание собственного HTTP-роутера на чистом PHP за 15 минут',
        'Сравнение производительности MySQL, PostgreSQL и SQLite в веб-приложениях',
        'Почему доступность (a11y) в веб-дизайне — это не опция, а необходимость',
        'Управление зависимостями в Composer: Тонкости оптимизации автозагрузки',
        'Как построить эффективный CI/CD пайплайн с автоматическим тестированием',
        'Тренды UI/UX дизайна: Минимализм, типографика и интерактивные анимации',
        'Анатомия HTTP-запроса: Что происходит от нажатия Enter до рендеринга страницы',
        'Проектирование RESTful API: Типичные ошибки новичков и лучшие практики',
        'Как успешно пройти техническое собеседование на позицию Senior PHP разработчика',
        'Мобильная оптимизация веб-сайтов: Скорость загрузки и Core Web Vitals',
        'Асинхронный PHP: Fibers, Swoole и будущее параллельных вычислений',
        'Безопасность сессий и куки: Как предотвратить перехват сессий (Hijacking)',
        'Основы Docker Compose для командной разработки без конфликтов окружений',
        'Паттерны проектирования в PHP: От Factory до Dependency Injection',
        'Оптимизация фронтенда: Минификация стилей и современный CSS Grid',
        'Как писать самодокументируемый код, который легко поддерживать годами',
        'Мониторинг ошибок в продакшне: Как узнавать о багах раньше пользователей',
        'Масштабирование веб-приложений: Кэширование, балансировка и репликация БД',
        'Разработка Progressive Web Apps (PWA): Как превратить сайт в мобильное приложение',
        'Карьерный путь в IT: Как не выгореть и постоянно развивать свои навыки',
    ];

    $imageTopics = ['technology', 'code', 'laptop', 'workspace', 'server', 'ai', 'mobile', 'design'];

    $postStmt = $db->prepare('
        INSERT INTO posts (title, slug, description, content, image_url, views_count, published_at)
        VALUES (:title, :slug, :description, :content, :image_url, :views_count, :published_at)
    ');

    $postCatStmt = $db->prepare('INSERT INTO post_categories (post_id, category_id) VALUES (:post_id, :category_id)');

    $postCount = 0;
    foreach ($postTitles as $index => $title) {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower(transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $title)));
        $slug = trim($slug, '-') . '-' . ($index + 1);

        $description = $faker->paragraph(2);

        // Generate rich HTML content
        $content = '<p>' . implode('</p><p>', $faker->paragraphs(4)) . '</p>';
        $content .= '<h3>Ключевые аспекты и выводы</h3>';
        $content .= '<ul>';
        $content .= '<li>' . $faker->sentence(8) . '</li>';
        $content .= '<li>' . $faker->sentence(10) . '</li>';
        $content .= '<li>' . $faker->sentence(9) . '</li>';
        $content .= '</ul>';
        $content .= '<p>' . implode('</p><p>', $faker->paragraphs(3)) . '</p>';

        // Deterministic reliable seed URL that never 404s
        $imageUrl = "https://picsum.photos/seed/{$slug}/800/450";

        $viewsCount = random_int(15, 3450);

        $daysAgo = random_int(0, 60);
        $hoursAgo = random_int(0, 23);
        $publishedAt = (new DateTimeImmutable("-{$daysAgo} days -{$hoursAgo} hours"))->format('Y-m-d H:i:s');

        $postStmt->execute([
            'title'        => $title,
            'slug'         => $slug,
            'description'  => $description,
            'content'      => $content,
            'image_url'    => $imageUrl,
            'views_count'  => $viewsCount,
            'published_at' => $publishedAt,
        ]);

        $postId = (int)$db->lastInsertId();

        // Assign 1 to 3 random categories to each post
        $assignedCount = random_int(1, 3);
        $shuffledCategories = $categoryIds;
        shuffle($shuffledCategories);
        $assignedCategories = array_slice($shuffledCategories, 0, $assignedCount);

        foreach ($assignedCategories as $catId) {
            $postCatStmt->execute([
                'post_id'     => $postId,
                'category_id' => $catId,
            ]);
        }

        $postCount++;
    }

    echo "[3/4] Успешно создано {$postCount} статей со связями Many-to-Many.\n";
    echo "[4/4] Наполнение базы данных завершено!\n";
} catch (Throwable $e) {
    echo "ОШИБКА сидера: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
