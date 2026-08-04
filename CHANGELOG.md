# Changelog

Формат — [Keep a Changelog](https://keepachangelog.com/ru/1.0.0/).

## [1.0.0] — 2026-08-04

### Added
- Поле «Чат с клиентом прочитан?» в стандартном фильтре Лидов и Сделок CRM.
- Фильтрация по данным штатной таблицы бейджей CRM (`b_crm_item_badge`, `open_line_status`/`not_read_chat`) — без собственной синхронизации чатов открытых линий.
- Подмена DI-сервиса `crm.filter.factory` через `lib/FilterFactory.php` + `lib/EventHandler.php` (`OnProlog`).
