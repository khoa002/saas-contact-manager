# SaaS Contact Manager

This is a simple contact manager app built with Laravel 7

## Set up

The following instructions assumes you have the typical LAMP stack installed locally that's able to run Laravel.
If you run into issues with the following instructions, more than likely your local environment doesn't have the
correct dependencies.

1. Clone `git@github.com:khoa002/saas-contact-manager.git`
2. Compile the project with:
    * `composer install`
3. Copy `.env.example` to your local `.env` and modify the db connection credentials to match your local (the database needs to be created manually)
    ```
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=saas_contact_manager
    DB_USERNAME=root
    DB_PASSWORD=root
    ```
4. Also make sure that the following are set to use the KLAVIYO_SYNC feature
    ```
   KLAVIYO_SYNC_ENABLED=true
   KLAVIYO_API_ENDPOINT=https://a.klaviyo.com
   KLAVIYO_DEFAULT_CONTACTS_LIST_NAME=Contacts
   KLAVIYO_PUBLIC_KEY=[your_public_key_goes_here]
   KLAVIYO_API_TOKEN=[your_api_token_goes_here]
    ```
5. Run `php artisan key:generate` to generate an app key
6. Run `php artisan migrate` to migrate the database
7. If you don't already have a local server running, run `php artisan serve` and access your site locally at http://localhost:8000/
8. You are now free explore the app.
9. Sample CSV files are included in `storage/app/samples` of this repository.
