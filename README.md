##Installation

Install Xampp 8.2
Install Composer

##XAMPP To-do
Open C:\xampp\php\php.ini
Ctr + F "zip" "intl"
remove the ";" infront of the zip and intl
restart your Apatche and Mysqll

##When Pulling from repo
```sh
composer update
```

##CREATE .env file from the root folder
> php artisan migrate
> php artisan migrate:fresh
> php artisan serve
> IF IT ASK FOR THE APP KEY USE THIS:

```sh
php artisan key:generate

```


