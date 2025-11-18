
# Kaledo Loyalty API

This project is the backend API for the Kaledo Loyalty application, built with the Laravel framework.

## Getting Started

You can run this project using either Laravel Sail (our recommended Docker-based environment) or by setting up a local development environment on your machine.

### Prerequisites

Before you begin, ensure you have the following installed:

**For Laravel Sail (Docker):**
- Docker Desktop

**For Local Environment:**
- PHP 8.2 or higher
- Composer
- Node.js & npm
- A database server (like MySQL, PostgreSQL, or SQLite)

---

### Option 1: Running the Project with Laravel Sail (Recommended)

Laravel Sail provides a simple command-line interface for interacting with Laravel's default Docker development environment.

**1. Clone the Project**
```bash
git clone <repository-url>
cd <project-folder>
```

**2. Install Composer Dependencies**
This command runs a temporary Docker container to install the required PHP packages.

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php83-composer:latest \
    composer install --ignore-platform-reqs
```

**3. Build and Run the Sail Containers**
This will build the necessary Docker images and start the services in the background.

```bash
./vendor/bin/sail up -d
```
*Note: You can create a shell alias to make `sail` easier to use: `alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'`*

**4. Set Up Environment File**
Copy the example environment file and generate the application key.

```bash
cp .env.example .env
sail artisan key:generate
```

**5. Install Laravel Passport Keys**
This command creates the encryption keys needed for generating access tokens.

```bash
sail artisan passport:install
```

**6. Run Database Migrations**
This will create the necessary tables in your database.

```bash
sail artisan migrate
```

**7. Install Node.js Dependencies**
```bash
sail npm install
```

**8. Run the Development Server**
The application will be available at `http://localhost`.

```bash
sail npm run dev
```

---

### Option 2: Running the Project on Your Local Machine (macOS, Windows, or Linux)

If you prefer not to use Docker, you can set up the project directly on your machine.

**1. Clone the Project**
```bash
git clone <repository-url>
cd <project-folder>
```

**2. Install Composer Dependencies**
```bash
composer install
```

**3. Set Up Environment File**
Copy the example `.env` file.
```bash
cp .env.example .env
```
Next, open the `.env` file and configure your database connection details (DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD).

**4. Generate Application Key**
```bash
php artisan key:generate
```

**5. Install Laravel Passport Keys**
```bash
php artisan passport:install
```

**6. Run Database Migrations**
```bash
php artisan migrate
```

**7. Install Node.js Dependencies**
```bash
npm install
```

**8. Start the Development Server**
This command starts the local PHP server. By default, it will be available at `http://localhost:8000`.

```bash
php artisan serve
```
In a separate terminal, run the Vite development server for frontend assets:

```bash
npm run dev
```
Now your API is up and running! You can access it at the local server address.
