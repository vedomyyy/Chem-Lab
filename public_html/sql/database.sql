SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- База данных
-- ============================================================
CREATE DATABASE IF NOT EXISTS `chem_lab_inventory`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `chem_lab_inventory`;

-- ============================================================
-- Таблица: categories (категории материалов)
-- ============================================================
CREATE TABLE `categories` (
  `id`   int          NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Кислоты'),
(2, 'Щёлочи'),
(3, 'Соли'),
(4, 'Растворители'),
(5, 'Стекло'),       -- категория из ТЗ
(6, 'Оборудование');

-- ============================================================
-- Таблица: suppliers (поставщики)
-- ============================================================
CREATE TABLE `suppliers` (
  `id`      int          NOT NULL AUTO_INCREMENT,
  `name`    varchar(200) NOT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `phone`   varchar(20)  DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `suppliers` (`id`, `name`, `contact`, `phone`) VALUES
(1, 'ХимРеактив ООО',   'Иванов П.А.',    '+7 495 123-45-67'),
(2, 'ЛабСнаб ЗАО',      'Петрова Е.М.',   '+7 812 987-65-43'),
(3, 'РеактивТорг ИП',   'Сидоров К.В.',   '+7 343 555-00-11');

-- ============================================================
-- Таблица: materials (склад материалов)
-- ============================================================
CREATE TABLE `materials` (
  `id`          int            NOT NULL AUTO_INCREMENT,
  `name`        varchar(200)   NOT NULL,
  `formula`     varchar(100)   DEFAULT NULL,          -- химическая формула
  `category_id` int            NOT NULL,
  `unit`        varchar(20)    NOT NULL,               -- ед. измерения: г, мл, шт
  `quantity`    decimal(10,3)  NOT NULL DEFAULT '0.000', -- текущий остаток
  `location`    varchar(100)   DEFAULT NULL,            -- место хранения
  `threshold`   decimal(10,3)  DEFAULT '0.000',         -- порог заказа
  `created_at`  timestamp      DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `materials_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 25 материалов (все из ТЗ + дополнительные)
INSERT INTO `materials` (`id`, `name`, `formula`, `category_id`, `unit`, `quantity`, `location`, `threshold`) VALUES
-- Кислоты (category_id = 1)
(1,  'Соляная кислота',          'HCl',      1, 'мл',  1200.000, 'Шкаф А, полка 1',  500.000),
(2,  'Серная кислота',           'H2SO4',    1, 'мл',   800.000, 'Шкаф А, полка 1',  300.000),
(3,  'Азотная кислота',          'HNO3',     1, 'мл',   450.000, 'Шкаф А, полка 2',  300.000),
(4,  'Уксусная кислота',         'CH3COOH',  1, 'мл',   600.000, 'Шкаф А, полка 2',  200.000),
(5,  'Фосфорная кислота',        'H3PO4',    1, 'мл',   280.000, 'Шкаф А, полка 3',  150.000),
-- Щёлочи (category_id = 2)
(6,  'Гидроксид натрия',         'NaOH',     2, 'г',    600.000, 'Шкаф Б, полка 1',  200.000),
(7,  'Гидроксид калия',          'KOH',      2, 'г',    350.000, 'Шкаф Б, полка 1',  150.000),
(8,  'Гидроксид аммония 25%',    'NH4OH',    2, 'мл',   500.000, 'Шкаф Б, полка 2',  200.000),
-- Соли (category_id = 3)
(9,  'Хлорид натрия',            'NaCl',     3, 'г',    900.000, 'Шкаф В, полка 1',  300.000),
(10, 'Карбонат натрия',          'Na2CO3',   3, 'г',    700.000, 'Шкаф В, полка 1',  200.000),
(11, 'Сульфат меди',             'CuSO4',    3, 'г',    280.000, 'Шкаф В, полка 2',  100.000),
(12, 'Хлорид бария',             'BaCl2',    3, 'г',    150.000, 'Шкаф В, полка 2',   80.000),
(13, 'Нитрат серебра',           'AgNO3',    3, 'г',     50.000, 'Шкаф В, полка 3',   20.000),
(14, 'Сульфат аммония',          '(NH4)2SO4',3, 'г',    320.000, 'Шкаф В, полка 3',  100.000),
-- Растворители (category_id = 4)
(15, 'Дистиллированная вода',    'H2O',      4, 'мл', 10000.000, 'Шкаф Г, полка 1', 2000.000),
(16, 'Этиловый спирт 96%',       'C2H5OH',   4, 'мл',  2000.000, 'Шкаф Г, полка 2',  500.000),
(17, 'Ацетон',                   'C3H6O',    4, 'мл',   800.000, 'Шкаф Г, полка 2',  300.000),
(18, 'Диэтиловый эфир',          'C4H10O',   4, 'мл',   400.000, 'Шкаф Г, полка 3',  150.000),
-- Стекло (category_id = 5)
(19, 'Пробирки стеклянные 10 мл',NULL,       5, 'шт',    80.000, 'Шкаф Д, полка 1',   30.000),
(20, 'Колбы конические 250 мл',  NULL,       5, 'шт',    35.000, 'Шкаф Д, полка 1',   15.000),
(21, 'Стаканы химические 100 мл',NULL,       5, 'шт',    40.000, 'Шкаф Д, полка 2',   20.000),
(22, 'Воронки стеклянные',       NULL,       5, 'шт',    25.000, 'Шкаф Д, полка 2',   10.000),
(23, 'Бюретки 25 мл',            NULL,       5, 'шт',    12.000, 'Шкаф Д, полка 3',    5.000),
-- Оборудование (category_id = 6)
(24, 'Пипетки мерные 1 мл',      NULL,       6, 'шт',    18.000, 'Шкаф Е, полка 1',   10.000),
(25, 'Шпатели металлические',    NULL,       6, 'шт',    30.000, 'Шкаф Е, полка 1',   10.000);

-- ============================================================
-- Таблица: users (пользователи)
-- ============================================================
CREATE TABLE `users` (
  `id`            int          NOT NULL AUTO_INCREMENT,
  `username`      varchar(50)  NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role`          enum('lab_assistent','teacher','admin') NOT NULL DEFAULT 'lab_assistent',
  `full_name`     varchar(100) NOT NULL,
  `created_at`    timestamp    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Пароли:
--   admin    → password
--   teacher1 → password
--   lab1, lab2, lab3 → lab123
INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `full_name`) VALUES
(1, 'admin',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',         'Администратов Алексей Иванович'),
(2, 'teacher1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher',       'Профессорова Мария Сергеевна'),
(3, 'lab1',     '$2y$10$TKh8H1.PfkxbW.7H7oMXC.f36g3g5RK3G0uZxHZuW9lzC5mTbv/K6', 'lab_assistent', 'Лаборантова Анна Петровна'),
(4, 'lab2',     '$2y$10$TKh8H1.PfkxbW.7H7oMXC.f36g3g5RK3G0uZxHZuW9lzC5mTbv/K6', 'lab_assistent', 'Реагентов Дмитрий Олегович'),
(5, 'lab3',     '$2y$10$TKh8H1.PfkxbW.7H7oMXC.f36g3g5RK3G0uZxHZuW9lzC5mTbv/K6', 'lab_assistent', 'Мензуркин Игорь Васильевич');

-- ============================================================
-- Таблица: transactions (операции со складом)
-- ============================================================
CREATE TABLE `transactions` (
  `id`          int           NOT NULL AUTO_INCREMENT,
  `material_id` int           NOT NULL,
  `type`        enum('in','out') NOT NULL,             -- приход / расход
  `quantity`    decimal(10,3) NOT NULL,
  `supplier_id` int           DEFAULT NULL,            -- для прихода
  `purpose`     text          DEFAULT NULL,             -- для расхода — цель
  `user_id`     int           NOT NULL,                -- кто сделал операцию
  `created_at`  timestamp     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `material_id` (`material_id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `user_id`     (`user_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `materials`  (`id`) ON DELETE CASCADE,
  CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`  (`id`) ON DELETE SET NULL,
  CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`user_id`)     REFERENCES `users`      (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 28 транзакций (приходы + расходы)
INSERT INTO `transactions` (`material_id`, `type`, `quantity`, `supplier_id`, `purpose`, `user_id`, `created_at`) VALUES
-- Приходы (плановые заказы)
(1,  'in',  500.000, 1, 'Плановый заказ январь 2026',          1, '2026-01-10 09:00:00'),
(6,  'in',  200.000, 2, 'Плановый заказ январь 2026',          1, '2026-01-10 09:05:00'),
(9,  'in',  300.000, 1, 'Пополнение запасов',                  1, '2026-01-10 09:10:00'),
(15, 'in', 5000.000, 3, 'Дистиллят — ежемесячная доставка',    1, '2026-01-15 10:00:00'),
(19, 'in',   50.000, 2, 'Заказ лабораторного стекла',          1, '2026-01-20 11:00:00'),
(16, 'in', 1000.000, 1, 'Пополнение этанола',                  1, '2026-02-01 09:00:00'),
(24, 'in',   20.000, 2, 'Заказ пипеток',                       1, '2026-02-05 10:00:00'),
(11, 'in',  100.000, 1, 'Заказ сульфата меди',                 1, '2026-02-10 09:00:00'),
-- Расходы (лабораторные работы)
(1,  'out', 100.000, NULL, 'Лаб. работа №1: Кислотно-основные реакции',   3, '2026-01-12 10:30:00'),
(1,  'out',  50.000, NULL, 'Лаб. работа №3: Гидролиз солей',              4, '2026-01-18 11:00:00'),
(6,  'out',  80.000, NULL, 'Лаб. работа №1: Кислотно-основные реакции',   3, '2026-01-12 10:35:00'),
(6,  'out',  40.000, NULL, 'Лаб. работа №4: Нейтрализация',               5, '2026-01-25 14:00:00'),
(9,  'out', 150.000, NULL, 'Лаб. работа №2: Получение осадков',           4, '2026-01-14 09:30:00'),
(11, 'out',  60.000, NULL, 'Демонстрационный опыт',                       5, '2026-01-16 13:00:00'),
(15, 'out', 500.000, NULL, 'Лаб. работа №2: Приготовление растворов',     3, '2026-01-17 09:00:00'),
(16, 'out', 200.000, NULL, 'Лаб. работа №4: Экстракция',                  4, '2026-01-22 10:00:00'),
(19, 'out',   5.000, NULL, 'Лаб. работа №1: Кислотно-основные реакции',   3, '2026-01-12 10:20:00'),
(20, 'out',   3.000, NULL, 'Лаб. работа №3: Гидролиз солей',              4, '2026-01-18 10:50:00'),
(2,  'out',  80.000, NULL, 'Лаб. работа №5: Электрохимия',                5, '2026-02-03 11:00:00'),
(7,  'out',  50.000, NULL, 'Лаб. работа №5: Электрохимия',                3, '2026-02-03 11:05:00'),
(10, 'out', 100.000, NULL, 'Лаб. работа №6: Осаждение карбонатов',        4, '2026-02-08 10:00:00'),
(12, 'out',  30.000, NULL, 'Лаб. работа №6: Осаждение карбонатов',        4, '2026-02-08 10:05:00'),
(17, 'out', 100.000, NULL, 'Лаб. работа №7: Экстракция органических вещ.',5, '2026-02-12 09:30:00'),
(24, 'out',   2.000, NULL, 'Лаб. работа №2: Приготовление растворов',     3, '2026-02-15 09:00:00'),
(16, 'out', 150.000, NULL, 'Лаб. работа №8: Хроматография',               4, '2026-03-01 10:00:00'),
(1,  'out',  80.000, NULL, 'Лаб. работа №8: Хроматография',               5, '2026-03-01 10:10:00'),
(15, 'out', 800.000, NULL, 'Лаб. работа №9: Количественный анализ',       3, '2026-03-05 09:00:00'),
(13, 'out',  10.000, NULL, 'Лаб. работа №9: Количественный анализ',       4, '2026-03-05 09:05:00');

COMMIT;