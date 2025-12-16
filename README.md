# Trading Platform

This is a full-stack cryptocurrency trading application built with **Laravel (PHP)** and **Vue 3**, running inside **Docker**. It includes real-time updates via **Laravel Echo / Pusher**.

---

## 🛠 Requirements

- Docker & Docker Compose installed
- Ports **8000** and **3307** available on your machine

---

## 🚀 Quick Start

1. **Clone the repository**

```bash
git clone https://github.com/rowinsbie/exchange-assessment.git
cd exchange-assessment
```
2. **.env setup**
```bash
copy the .env.example contents and paste to .env
```
 Please make sure to add your pusher  configurations

3. **Build and run Docker containers**
```bash
docker compose up --build
docker compose exec app sh
composer install
php artisan key:generate
```
4. **Access to browser **
```bash
http://localhost:8000/
```

5. Login, 2 default users
```bash
test1@seikirowinsbie.com
test2@seikirowinsbie.com
```
