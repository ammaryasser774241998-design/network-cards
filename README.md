<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Deploying to Render

Create a Render Blueprint from the GitHub repository. [`render.yaml`](./render.yaml) reuses the existing `network-cards` web service on Render's free plan and connects it to a PostgreSQL database hosted separately on Supabase Free. The Render web service can sleep when idle and has an ephemeral filesystem. Supabase Free currently includes 500 MB of database storage, pauses projects after one week of inactivity, and does not include automatic backups; review the [current plan limits](https://supabase.com/pricing) before using it for production data. The Docker image builds the Vite assets, installs production PHP dependencies, and listens on Render's `PORT` (default `10000`).

The Blueprint prompts for `APP_KEY` and `DATABASE_URL`. Create a Supabase PostgreSQL project, choose its **Session pooler** connection string, and add `sslmode=require` if it is not already present. Enter both values directly into Render's protected prompt; never put production secrets in `.env`, source code, or GitHub. Generate an application key with `php artisan key:generate --show`. Laravel uses `RENDER_EXTERNAL_URL` as the public URL unless you set `APP_URL` yourself.

### Import the existing local database

The Supabase database starts empty; deploying the code does not copy data or login accounts from local MySQL. After the first successful deployment:

1. Back up the local database and verify the deployed service has completed all migrations.
2. Keep the Supabase Session pooler connection URL available locally. Do not paste it into source code, `.env`, or chat.
3. On the local Windows machine, load the XAMPP PostgreSQL PDO extension for that command and run a dry run:

   ```powershell
   php -d extension=php_pdo_pgsql.dll artisan database:import-render
   ```

   Enter the Supabase connection URL at the hidden prompt. The command displays table names and row counts only. It never writes during a dry run.

4. If the source and destination are correct, run the import:

   ```powershell
   php -d extension=php_pdo_pgsql.dll artisan database:import-render --run
   ```

   Confirm the prompt to copy application records. The command refuses a non-empty or unmigrated destination, compares table schemas, copies rows in dependency order inside a transaction, and resets PostgreSQL ID sequences. Browser sessions, password-reset tokens, and personal API tokens are intentionally left behind; users and password hashes are copied so existing login credentials continue to work.

The import transfers sensitive customer, card, and financial records to Supabase. Review your privacy and retention requirements before confirming. Uploaded files are not part of the database import, and Render's free filesystem loses uploads on restart or deploy; configure external object storage separately if those files must persist. Keep independent database backups because Supabase Free does not include automatic backups.


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
