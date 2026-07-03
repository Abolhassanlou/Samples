# Professional PHP MVC Online Shop

This project is a simple but professional PHP MVC Online Shop built with XAMPP, Composer, Dotenv, PDO, and a clean folder structure.
The goal of this project is to learn how to build a PHP project step by step and prepare it for GitHub as a portfolio project.

---

## 1. Requirements

Before starting, install the following tools:

* XAMPP
* PHP
* Composer
* VS Code
* Git

---

## 2. Project Location

If you use XAMPP, create the project inside the `htdocs` folder:

```powershell
cd C:\xampp\htdocs
mkdir OnlineShop
cd OnlineShop
```

---

## 3. Check PHP Installation

First, check if PHP is available:

```powershell
php -v
```

If this command does not work, check whether PHP exists in:

```text
C:\xampp\php\php.exe
```

You can test it directly:

```powershell
C:\xampp\php\php.exe -v
```

If it works, add this path to Windows PATH:

```text
C:\xampp\php
```

After that, restart PowerShell or VS Code and test again:

```powershell
php -v
```

---

## 4. Install Composer

Download Composer for Windows:

```text
https://getcomposer.org/download/
```

During installation, select the PHP executable from XAMPP:

```text
C:\xampp\php\php.exe
```

After installation, restart VS Code or PowerShell and check Composer:

```powershell
composer -V
```

If Composer is installed in a custom folder such as:

```text
C:\composer
```

make sure this folder is added to the Windows PATH.

---

## 5. Create the Project Structure

OnlineShop/
│
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── config/
│
├── controller/
│   ├── AuthController.php
│   └── ProductController.php
│
├── middleware/
│   ├── AuthMiddleware.php
│   └── AdminMiddleware.php
│
├── model/
│   ├── DB.php
│   ├── User.php
│   └── Product.php
│
├── requests/
│   ├── RegisterRequest.php
│   ├── LoginRequest.php
│   └── ProductRequest.php
│
├── templates/
│   │
│   ├── auth/
│   │   ├── login.php
│   │   ├── register.php
│   │   └── logout.php
│   │
│   ├── dashboard/
│   │   └── dashboard.php
│   │
│   ├── product/
│   │   ├── index.php
│   │   ├── create.php
│   │   ├── edit.php
│   │   └── show.php
│   │
│   └── layout/
│       ├── header.php
│       ├── sidebar.php
│       └── footer.php
│
├── vendor/
├── .env
├── .env.example
├── bootstrap.php
├── composer.json
├── composer.lock
├── README.md
└── index.php

# Project Architecture Rules

## Model
- Handles all database operations.
- Contains SQL queries only.
- Does not contain HTML or business logic.

## Request
- Validates user input.
- Returns validation errors.
- Does not communicate with the database (except when absolutely necessary).

## Controller
- Contains the application business logic.
- Receives data from the View.
- Calls Request classes for validation.
- Calls Model classes for database operations.
- Returns data or redirects to the appropriate View.
- Does not contain SQL queries or HTML.

## Templates (Views)
- Responsible only for displaying data.
- Contains HTML with minimal PHP.
- Does not communicate directly with the database.

## Middleware
- Controls access to protected pages.
- Checks authentication and authorization.
- Redirects unauthorized users when necessary.

## Assets
- Stores static files such as:
  - CSS
  - JavaScript
  - Images

## Config
- Stores application configuration files.
- Environment-specific settings.

## General Rules
- Follow the MVC architecture.
- Follow the PSR-12 coding standard.
- Use PDO with prepared statements.
- Never write raw SQL inside Controllers.
- Never place business logic inside Views.
- Validate all user input before processing.
- Hash passwords using `password_hash()`.
- Verify passwords using `password_verify()`.
- Store sensitive configuration in the `.env` file.
- Keep code modular, reusable, and maintainable.

Inside the project folder, create the folders:

```powershell
mkdir controller,model,requests,middleware,templates,img,css,js
```

Create the main files:

```powershell
ni index.php
ni login.php
ni register.php
ni logout.php
ni products.php
ni product.php
ni cart.php
ni checkout.php
ni profile.php
ni admin.php
```

Create controller files:

```powershell
ni controller\AuthController.php
ni controller\ProductController.php
ni controller\CartController.php
ni controller\OrderController.php
ni controller\AdminController.php
```

Create model files:

```powershell
ni model\DB.php
ni model\User.php
ni model\ProductModel.php
ni model\CartModel.php
ni model\OrderModel.php
```

Create request validation files:

```powershell
ni requests\RegisterRequest.php
ni requests\LoginRequest.php
ni requests\ProductRequest.php
ni requests\CheckoutRequest.php
```

Create middleware files:

```powershell
ni middleware\IsGuest.php
ni middleware\IsUser.php
ni middleware\IsAdmin.php
```

Create template files:

```powershell
ni templates\header.php
ni templates\footer.php
ni templates\navbar.php
```

Create asset files:

```powershell
ni css\style.css
ni js\script.js
```

Create GitHub and environment files:

```powershell
ni .env
ni .env.example
ni .gitignore
ni README.md
```

---

## 6. Final Folder Structure

```text
OnlineShop/
│
├── controller/
│   ├── AuthController.php
│   ├── ProductController.php
│   ├── CartController.php
│   ├── OrderController.php
│   └── AdminController.php
│
├── model/
│   ├── DB.php
│   ├── User.php
│   ├── ProductModel.php
│   ├── CartModel.php
│   └── OrderModel.php
│
├── requests/
│   ├── RegisterRequest.php
│   ├── LoginRequest.php
│   ├── ProductRequest.php
│   └── CheckoutRequest.php
│
├── middleware/
│   ├── IsGuest.php
│   ├── IsUser.php
│   └── IsAdmin.php
│
├── templates/
│   ├── header.php
│   ├── footer.php
│   └── navbar.php
│
├── img/
├── css/
│   └── style.css
│
├── js/
│   └── script.js
│
├── index.php
├── register.php
├── login.php
├── logout.php
├── products.php
├── product.php
├── cart.php
├── checkout.php
├── profile.php
├── admin.php
│
├── .env
├── .env.example
├── .gitignore
├── composer.json
├── composer.lock
└── README.md
```

---

## 7. Initialize Composer
## 7. Initialize Composer

Navigate to the project directory:

```powershell
cd C:\xampp\htdocs\OnlineShop
```

Initialize Composer:

```powershell
composer init
```

Example configuration:

```text
Package name: abolhassanlou/onlineshop
Description: Professional PHP MVC Online Shop
Author: Hellen Abolhassani <abolhassanlou@gmail.com>
Minimum Stability: stable
Package Type: project
License: MIT
```

Confirm the configuration by typing:

```text
yes
```

---

## 8. Install Project Dependencies

Install Dotenv using Composer:

```powershell
composer require vlucas/phpdotenv
```

This command will:

- Create the `vendor/` directory.
- Generate the `composer.lock` file.
- Install the `vlucas/phpdotenv` package.
- Generate Composer's autoloader.

After installing the package, verify that the following files exist:

```text
vendor/
composer.json
composer.lock
```

## 8. Install phpdotenv

Install Dotenv with Composer:

```powershell
composer require vlucas/phpdotenv
```

This package allows the project to read sensitive configuration from the `.env` file.

---

## 9. Fix ZIP Extension Error if Needed

If Composer shows this error:

```text
The zip extension and unzip/7z commands are both missing
```

open this file:

```text
C:\xampp\php\php.ini
```

Find this line:

```ini
;extension=zip
```

Remove the semicolon:

```ini
extension=zip
```

Save the file, restart VS Code or PowerShell, and test:

```powershell
php -m | findstr zip
```

Then run again:

```powershell
composer require vlucas/phpdotenv
```

---

## 10. Environment Configuration

Create a `.env` file:

```env
APP_NAME=OnlineShop
APP_ENV=development

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=onlineshop
DB_USERNAME=root
DB_PASSWORD=
```

Create a `.env.example` file:

```env
APP_NAME=OnlineShop
APP_ENV=development

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=database_name
DB_USERNAME=username
DB_PASSWORD=password
```

The `.env` file contains private configuration and should not be uploaded to GitHub.

---

## 11. Git Ignore

Add this to `.gitignore`:

```gitignore
/vendor/
/.env
/.idea/
/.vscode/
```

Important:

* `vendor/` should not be uploaded to GitHub.
* `.env` should not be uploaded to GitHub.
* Other developers can run `composer install` to install dependencies.

---

## 12. Project Development Order

The project can be developed in this order:

```text
1. Project structure
2. Composer setup
3. Dotenv setup
4. Database connection with PDO
5. Templates: header, navbar, footer
6. Register system
7. Login system
8. Session handling
9. Middleware: IsGuest, IsUser, IsAdmin
10. Products table
11. Product model and controller
12. Product listing page
13. Shopping cart
14. Checkout
15. Orders
16. Admin panel
17. GitHub documentation
```

---

## 13. Planned Features

* User registration
* User login
* Secure password hashing
* Session-based authentication
* Role-based middleware
* Product listing
* Shopping cart
* Checkout process
* Admin dashboard
* PDO database connection
* Environment configuration with Dotenv
* GitHub-ready project structure

---

## 14. Technologies Used

* PHP
* MySQL / MariaDB
* XAMPP
* Composer
* vlucas/phpdotenv
* PDO
* Bootstrap
* HTML
* CSS
* JavaScript
* Git / GitHub

---

## 15. Run the Project

Start Apache and MySQL in XAMPP.

Then open the project in the browser:

```text
http://localhost/OnlineShop
```

---

## 16. Install Dependencies After Cloning

If someone clones the project from GitHub, they should run:

```powershell
composer install
```

Then they should copy `.env.example` to `.env` and update the database settings.

```powershell
copy .env.example .env
```

---


## 9. Create the Database

This project uses **MySQL/MariaDB**.

### Step 1: Start XAMPP

Make sure the following services are running:

- Apache
- MySQL

---

### Step 2: Open phpMyAdmin

Open your browser and go to:

```text
http://localhost/phpmyadmin
```

---

### Step 3: Create a Database

Click **New** in the left sidebar.

Create a new database with the following name:

```text
onlineshop
```

Choose the collation:

```text
utf8mb4_unicode_ci
```

Click **Create**.

---

### Step 4: Import the Database Schema

Select the **onlineshop** database.

Click the **Import** tab.

Choose the following file:

```text
database/schema.sql
```

Click **Go**.

---

### Step 5: Verify the Import

After importing the SQL file, you should see the following tables:

```text
onlineshop
│
├── users
├── categories
├── products
├── carts
├── cart_items
├── orders
└── order_items
```

If all tables are visible, the database has been successfully created.

---

### Why Use a SQL Schema?

Instead of creating tables manually in phpMyAdmin, the project uses a SQL schema file.

This approach offers several advantages:

- Database structure is version-controlled with Git.
- Every developer works with the same database structure.
- Setting up the project is faster.
- The database can be recreated at any time with a single import.
- Ideal for collaboration and deployment.


## Testing the Database Connection

For development purposes, a temporary database test file can be created inside the `dev` folder.

Example structure:

```text
OnlineShop/
│
├── bootstrap.php
├── model/
│   └── DB.php
│
└── dev/
    └── test_db.php
```

Because `test_db.php` is inside the `dev` folder, paths must go one level back to access project files.

Example:

```php
require_once '../bootstrap.php';
require_once '../model/DB.php';
```

The `bootstrap.php` file must be loaded first because it loads Composer, Dotenv, and environment variables from the `.env` file.

Without loading `bootstrap.php`, values such as:

```php
$_ENV['DB_HOST']
$_ENV['DB_DATABASE']
$_ENV['DB_USERNAME']
$_ENV['DB_PASSWORD']
```

will not be available.

A common mistake is using this path inside `dev/test_db.php`:

```php
require_once 'model/DB.php';
```

This is wrong because PHP will search for:

```text
OnlineShop/dev/model/DB.php
```

But the correct file is located at:

```text
OnlineShop/model/DB.php
```

Therefore, the correct relative path is:

```php
require_once '../model/DB.php';
```

After testing the database connection successfully, the test file should be removed or kept only for local development. It should not be part of production code.



حتماً. این را می‌توانی مستقیماً داخل `README.md` قرار بدهی.

# Development Progress

## Step 1 – Project Structure

The project follows the MVC (Model–View–Controller) architecture.

```
OnlineShop/
│
├── assets/
├── config/
├── controller/
├── dev/
├── img/
├── middleware/
├── model/
├── requests/
├── templates/
├── vendor/
├── .env
├── .env.example
├── .gitignore
├── bootstrap.php
├── composer.json
├── index.php
└── README.md
```

---

# Step 2 – Composer

Composer was installed to manage project dependencies.

Initialize the project:

```bash
composer init
```

Install Dotenv:

```bash
composer require vlucas/phpdotenv
```

---

# Step 3 – Environment Variables

A `.env` file is used to store sensitive configuration such as database credentials.

Example:

```env
DB_HOST=localhost
DB_DATABASE=onlineshop
DB_USERNAME=root
DB_PASSWORD=
```

Environment variables are loaded inside `bootstrap.php`.

---

# Step 4 – Bootstrap

`bootstrap.php` is responsible for preparing the application before any page is executed.

Responsibilities:

* Load Composer Autoloader
* Load Dotenv
* Load Environment Variables
* Start PHP Session

Every entry point (such as `index.php`, `register.php`, `login.php`) should include:

```php
require_once 'bootstrap.php';
```

---

# Step 5 – Database

A MariaDB/MySQL database named `onlineshop` was created.

The first table is `users`.

Columns:

* id
* first_name
* last_name
* email
* password
* is_admin
* created_at
* updated_at

---

# Step 6 – Database Connection

A reusable `DB` class was created.

Responsibilities:

* Read database credentials from `.env`
* Create a PDO connection
* Return the connection using:

```php
getConnection()
```

The connection is created only once inside the constructor.

---

# Step 7 – Testing the Database

A temporary file (`dev/test_db.php`) was created to test the database connection.

Example structure:

```
OnlineShop/
│
├── model/
│   └── DB.php
│
└── dev/
    └── test_db.php
```

Because the test file is inside `dev`, the correct include path is:

```php
require_once '../bootstrap.php';
require_once '../model/DB.php';
```

After confirming the connection, the test file can be removed.

---

# Step 8 – User Model

The first Model created is `User.php`.

Responsibilities:

* Communicate with the `users` table.
* Execute SQL queries.
* Never perform validation.
* Never contain HTML.

Current methods:

* `getUserByEmail()`
* `create()`

Future methods:

* `getUserById()`
* `all()`
* `update()`
* `delete()`

---

# Step 9 – RegisterRequest

The `RegisterRequest` class is responsible only for validation.

Responsibilities:

* Validate first name
* Validate last name
* Validate email
* Validate password
* Check whether the email already exists

If validation fails, an array of errors is returned.

---

# MVC Responsibilities

## Model

Responsible for:

* Database communication
* CRUD operations
* SQL queries

Never responsible for:

* HTML
* Bootstrap
* Validation
* Sessions

---

## Request

Responsible for:

* Input validation
* Returning validation errors

Never responsible for:

* Database insertion
* HTML rendering

---

## Controller

Responsible for:

* Connecting Models and Views
* Executing business logic
* Redirecting users

---

## View

Responsible only for displaying data.

Examples:

* register.php
* login.php
* index.php

Views should never communicate directly with the database.

---

# OOP Concepts Learned

## Inheritance

```php
class User extends DB
```

`User` inherits database functionality from the `DB` class.

Benefits:

* No duplicated database connection code.
* All models can reuse the same connection.

---

## Static

Static members belong to the class itself, not to an object.

Example:

```php
RegisterRequest::validate($data);
```

No object creation is required.

---

## Non-static

Non-static members belong to an object.

Example:

```php
$user = new User();
$user->getUserByEmail($email);
```

---

# self vs $this

Use `$this` for instance members.

```php
$this->getConnection();
```

Use `self::` for static members.

```php
self::$errors;
```

---

# Visibility

## public

Accessible from anywhere.

## private

Accessible only inside the same class.

## protected

Accessible inside the class and child classes.

---

# PDO Workflow

The standard workflow for every database query:

1. Get the database connection.
2. Write the SQL query.
3. Prepare the statement.
4. Bind parameters.
5. Execute the statement.
6. Fetch the result.
7. Return the result.

---

# Why Prepared Statements?

Instead of:

```php
SELECT * FROM users WHERE email = '$email'
```

Use:

```php
SELECT * FROM users WHERE email = :email
```

Then bind the value:

```php
$stmt->bindParam(':email', $email);
```

Benefits:

* Prevents SQL Injection
* Separates SQL from user input
* Improves security

---

# bindParam()

Example:

```php
$stmt->bindParam(':email', $email);
```

Explanation:

* `:email` is the placeholder inside the SQL query.
* `$email` is the PHP variable containing the user's input.

The value is safely bound before execution.

---

# Password Security

Passwords must never be stored as plain text.

Always hash passwords before inserting them into the database.

```php
password_hash($data['password'], PASSWORD_DEFAULT);
```

During login, verify the password using:

```php
password_verify()
```

---

# Current Progress

✅ Project Structure

✅ Composer

✅ Dotenv

✅ Bootstrap

✅ Environment Variables

✅ Database

✅ PDO Connection

✅ User Model

✅ RegisterRequest (Validation)

---

# Next Step

Create:

```
AuthController.php
```

Responsibilities:

* Call RegisterRequest
* Call User Model
* Register new users
* Return validation errors or success
