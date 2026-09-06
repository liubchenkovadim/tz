# Accommodation Offers REST API (Laravel)

REST API для асинхронного імпорту пропозицій житла від постачальників, пошуку найдешевшої актуальної пропозиції для кожної квартири/об'єкта та безпечного бронювання.

---

##  Технологічний стек
- **PHP 8.2+**
- **Laravel 11**
- **MySQL 8** (або SQLite для локального тестування)
- **Database Queue** (Laravel Queue Worker)
- **Docker / Docker Compose**

---

## 🛠 Інструкція з встановлення та запуску

### 1. Клонування та встановлення залежностей

```bash
git clone <repository_url>
cd tz1

# Встановлення PHP-залежностей через Composer
composer install
```

### 2. Конфігурація середовища

Створіть файл `.env` на основі `.env.example`:

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Запуск бази даних через Docker (MySQL 8)

Якщо ви використовуєте Docker Compose:

```bash
docker-compose up -d
```

Після запуску `docker-compose up -d` буде доступно:
- **Web API (Laravel)**: [http://localhost:8000](http://localhost:8000)
- **phpMyAdmin** (веб-інтерфейс БД): [http://localhost:8081](http://localhost:8081) (Сервер: `mysql`, Користувач: `root`, Пароль: `secret`)
- **MySQL 8** (зовнішній порт для хост-машини): `127.0.0.1:3307`

### Конфігурація `.env`:

1. **При роботі в Docker Compose** (контейнери `tz1-app` та `tz1-queue` звертаються до БД всередині мережі Docker):
```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=tz1
DB_USERNAME=root
DB_PASSWORD=secret

QUEUE_CONNECTION=database
```

2. **При виконанні команд `php artisan` безпосередньо на хост-машині** (поза Docker):
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=tz1
DB_USERNAME=root
DB_PASSWORD=secret

QUEUE_CONNECTION=database
```

---

##  Команди запуску міграцій, seeders, queue worker та тестів

### Виконання міграцій та заповнення початкових даних (Seeders)

Для створення таблиць та наповнення початковими постачальниками (`supplier-a`, `supplier-b`):

```bash
php artisan migrate:fresh --seed
```

### Запуск Queue Worker (Обробник черги для асинхронного імпорту)

Для обробки асинхронних завдань імпорту пропозицій:

```bash
php artisan queue:work
```

Запуск автоматизованих Feature-тестів через PHPUnit:

```bash
# Стандартний запуск
./vendor/bin/phpunit

# Детальний вивід назв усіх тестів та результатів (Testdox)
./vendor/bin/phpunit --testdox
```

---

##  Пояснення механізмів системи

### 1. Пояснення ідемпотентності імпорту (Import Idempotency)

Постачальники можуть відправляти один і той самий імпорт декілька разів (наприклад, через повторні спроби мережевого з'єднання). Для забезпечення ідемпотентності в системі реалізовано наступний механізм:

1. **База даних**: Таблиця `imports` містить унікальний составний індекс `(supplier_id, external_import_id)`.
2. **Endpoint `POST /api/imports`**:
   - Перед створенням нового запису контролер перевіряє наявність імпорту з переданими `supplier` та `external_import_id`.
   - Якщо такий імпорт **вже існує**: контролер одразу повертає статус `200 OK` із даними вже існуючого імпорту, **не створюючи дублікатів** і **не відправляючи нове завдання в чергу**.
   - Якщо імпорт **новий**: створюється запис у статусі `pending`, в чергу відправляється `ProcessImportJob`, і повертається відповідь `202 Accepted`.
3. **Обробка пропозицій**: Пропозиції всередині Job оновлюються через `updateOrCreate` за унікальним ключем `(supplier_id, external_id)`. Це гарантує, що повторний або оновлений імпорт не дублює пропозиції в базі даних, а актуалізує їхні ціни та наявність.

---

### 2. Захист від двох одночасних бронювань останньої одиниці (Race Condition Protection)

Для запобігання овербукінгу (подвійного бронювання останньої доступної одиниці `available_units = 1` при двох паралельних HTTP-запитах) застосовано підхід **Песимістичного Блокування (Pessimistic Locking)** на рівні транзакції бази даних:

1. **Транзакція бази даних (`DB::transaction`)**:
   - Весь процес створення бронювання обернений у транзакцію.
2. **Блокування рядка `lockForUpdate()`**:
   - При виконанні запиту `Offer::where('id', $id)->lockForUpdate()->firstOrFail()` база даних ставить ексклюзивне блокування (SQL `SELECT ... FOR UPDATE`) на конкретний рядок пропозиції.
3. **Обробка паралельних запитів**:
   - Якщо два запити приходять одночасно на останню одиницю (`available_units = 1`), перший запит захоплює блокування рядка.
   - Другий запит чекає на завершення транзакції першого запиту.
   - Перший запит перевіряє `available_units > 0`, зменшує `available_units` до `0`, створює запис у таблиці `reservations` та фіксує транзакцію (commit).
   - Коли другий запит отримує доступ до рядка, він бачить, що `available_units` вже дорівнює `0`, перевірка фейлиться і повертається помилка `422 Unprocessable Content` з повідомленням `"No available units remaining for this offer."`.

---

##  API Endpoints

### 1. Створення імпорту пропозицій
- **Endpoint**: `POST /api/imports`
- **Response**: `202 Accepted`

### 2. Перегляд статусу імпорту
- **Endpoint**: `GET /api/imports/{import}`
- **Response**: `200 OK`

### 3. Пошук актуального житла (з найдешевшою ціною)
- **Endpoint**: `GET /api/properties?city=Barcelona&check_in=2026-10-10&check_out=2026-10-15&guests=2`
- **Response**: `200 OK`

### 4. Бронювання пропозиції
- **Endpoint**: `POST /api/offers/{offer}/reservations`
- **Response**: `201 Created`

---

##  Postman Колекція

У корені проекту створено файл Postman-колекції:
- [tz1_postman_collection.json](tz1_postman_collection.json)

**Як імпортувати в Postman:**
1. Відкрийте Postman та натисніть **Import**.
2. Оберіть файл `tz1_postman_collection.json`.
3. Колекція містить змінні `base_url` (`http://localhost:8000`) та всі готові тестові запити для імпорту, перегляду статусу, пошуку та бронювання!

