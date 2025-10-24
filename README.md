## 📘 Documentation
Detailed project documentation & roadmap is available on Notion:  
👉 [Laravel E-Commerce MVP – Notion Page]([https://notion.so/firatkocoglu/Laravel-Ecommerce-MVP-a12bc345d678](https://www.notion.so/Laravel-E-Commerce-MVP-2960fe48dd9880bd86f3c9bec663ccd1?source=copy_link))

### 🛒 Laravel + Next.js E-Commerce App

A modern full-stack e-commerce application built with Laravel 12, Next.
This project demonstrates scalable architecture, clean code practices, and real-world features commonly needed in production-grade applications.

#🚀 Features
•	🔐 Authentication & Authorization (Laravel Breeze, Spatie Permissions for RBAC)

•	📦 Product Management (CRUD, categories, inventory, pagination)

•	🛍️ Shopping Cart & Checkout flow (React storefront)

•	💳 Order Management (statuses, history, invoices/receipts)

•	🔎 Advanced Search with Elasticsearch

•	⚡ Caching with Redis for performance optimization

•	📬 Asynchronous Messaging with RabbitMQ (Outbox pattern, event-driven workflows)

•	📝 Unit & Feature Tests with PHPUnit

•	📊 Admin Dashboard (Blade) with product, order, and customer insights

•	🌐 Storefront with Next.js (SEO-friendly, SSR/SSG)

⸻

#🏗️ Tech Stack

Backend
•	Laravel 12 (PHP 8.2+)

•	PostgreSQL (primary database)

•	Redis (cache/session)

•	RabbitMQ (message broker for async events & Outbox pattern)

•	Elasticsearch (search engine for products)

Frontend
•	Admin: Vue 3 + Inertia.js + Tailwind CSS

•	Storefront: Next.js (React, TypeScript, SSR/SSG, SEO optimized)

Tooling
•	Pint & Larastan (code quality & static analysis)

•	GitHub Actions (CI/CD planned)

⚙️ Installation

Prerequisites
•	PHP >= 8.2

•	Composer

•	Node.js & npm

Steps
### Clone the repository
git clone https://github.com/firatkocoglu/ecommerce.git

cd ecommerce

### Install backend dependencies
composer install

### Install frontend dependencies
npm install

### Copy environment file
cp .env.example .env

### Generate app key
php artisan key:generate

### Run migrations & seeders
php artisan migrate --seed

### Start development servers
php artisan serve

npm run dev

### Running tests
php artisan test

## 👨‍💻 Author

Developed by Fırat Koçoğlu
For freelancing and collaboration inquiries, feel free to connect!
