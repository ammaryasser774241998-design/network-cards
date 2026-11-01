<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Deploying to Render

Create a Render Blueprint from the GitHub repository and review its resources and prices before applying it. [`render.yaml`](./render.yaml) reuses the existing `network-cards` web service and creates a persistent PostgreSQL database in the same Virginia region. The web service uses Render's free plan; the database uses the paid `basic-256mb` plan. Upgrade the web service separately if you need it to remain always on. The Docker image builds the Vite assets, installs production PHP dependencies, and listens on Render's `PORT` (default `10000`).

The Blueprint prompts you to provide a stable `APP_KEY`, supplies the PostgreSQL connection URL, and runs migrations when the service starts. Generate the key with `php artisan key:generate --show` and enter it directly in Render's protected Blueprint prompt; do not put the production key in `.env` or commit it to GitHub. Laravel uses `RENDER_EXTERNAL_URL` as the public URL unless you set `APP_URL` yourself.

### Import the existing local database

The Render database starts empty; deploying the code does not copy data or login accounts from your local MySQL database. After the first successful deployment:

1. Back up the local database and verify the deployed service has completed all migrations.
2. In Render's PostgreSQL dashboard, copy the **external** connection URL. Do not paste it into source code, `.env`, or chat.
3. On the local Windows machine, load the XAMPP PostgreSQL PDO extension for that command and run a dry run:

   ```powershell
   php -d extension=php_pdo_pgsql.dll artisan database:import-render
   ```

   Enter the external database URL at the hidden prompt. The command displays table names and row counts only. It never writes during a dry run.

4. If the source and destination are correct, run the import:

   ```powershell
   php -d extension=php_pdo_pgsql.dll artisan database:import-render --run
   ```

   Confirm the prompt to copy application records. The command refuses a non-empty or unmigrated destination, compares table schemas, copies rows in dependency order inside a transaction, and resets PostgreSQL ID sequences. Browser sessions, password-reset tokens, and personal API tokens are intentionally left behind; users and password hashes are copied so existing login credentials continue to work.

The import transfers sensitive customer, card, and financial records to Render. Review your privacy and retention requirements before confirming. Uploaded files are not part of the database import; configure persistent storage separately if you need to keep them across deploys. Free Render web services can sleep when idle.


## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
