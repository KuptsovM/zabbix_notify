# Zabbix → Telegram (Symfony 7.4 LTS)

Порт Rust/Axum-сервиса. Ветка 7.2 больше не поддерживается и режется Composer из‑за security advisories, поэтому зависимости зафиксированы на `^7.4.23`.

- `POST /webhook/zabbix` — JSON `{"subject":"...","message":"..."}`, ответ `OK`
- отправка в Telegram Bot API (`sendMessage`)
- `GET /inbox` — список, новые сверху
- хранение в `var/alerts.json`

## Запуск

```bash
cp .env.example .env
composer install
php -S 0.0.0.0:8000 -t public
```

В `.env` пропиши `TELEGRAM_BOT_TOKEN` и `TELEGRAM_CHAT_ID`. Прокси, если нужен: `TELEGRAM_PROXY=http://user:pass@host:3128`.
