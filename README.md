# OneSignal Push Notifications for Laravel 5+

## Introduction

This project, widely inspired by Berkayk (https://github.com/berkayk/laravel-onesignal), is another one OneSignal wrapper for Laravel. The main purpose is to make web push notifications easier.
Before using this service, you'll need to complete all steps of your OneSignal setup (https://onesignal.com) to get your Application ID and API keys.

## Installation
1. Require package with composer  
```sh
composer require okn/laravel-onesignal
```
2. **Only for Laravel version < 5.5**  
Add the service provider and class alias for facade support in `config/app.php`
```php
'providers' => [
	// ...
	Okn\OneSignal\OneSignalServiceProvider::class
];

'aliases' => [
   	// ...
   	'OneSignal' => Okn\OneSignal\Facades\OneSignal::class
];
```

3. Run artisan command to install the service
```sh
php artisan onesignal:install
```
This command will create a default config file `config/onesignal.php`.

## Configuration
App ID et API keys must be defined in the `config/onesignal.php` file.

## Usage

### Default use
1. Create a notification
```php
$notification = OneSignal::createNotification([
    'headings'=>'Title',
    'contents'=>'Notification message...',
    'url'=>'https://yourwebsite.com'
]);
```

2. Send notification
* To segment(s)
```php
$notification->send(['segments'=>['SEGMENT-NAME']]);
```

* To specific user(s)
```php
$notification->send(['ids'=>['PLAYER-ID-1','PLAYER-ID-2','PLAYER-ID-3']]);
```

* Asynchronously
```php
$promise = $notification->async()->send([$params]);
```
This will return a `GuzzleHttp\Promise\Promise` (http://docs.guzzlephp.org/en/stable/quickstart.html#async-requests)

### Add buttons to notification
```php
$notification->withButons([
	[
		'id' => 'btnId1',
		'text' => 'Webpush button test',
		'icon' => 'https://yourwebsite.com/images/icon1.png',
		'url' => 'https://yourwebsite.com/action1'
	],
	[
		'id' => 'btnId2',
		'text' => 'Webpush button test #2',
		'icon' => 'https://yourwebsite.com/images/icon2.png',
		'url' => 'https://yourwebsite.com/action2'
	]
])->send([$params);
```

### Send a default template test notification
* To an existing segment named "Admin"  
```php
OneSignal::test();
```
This method also accepts the same argument as the method `send()`.

### Retrieve Users
1. All users
```php
OneSignal::getUsers(300, 0);
```
The first argument is the **maximum limit**, and the second is the **offset**. Both are optional.

2. Specific user
```php
OneSignal::getUser('PLAYER-ID');
```

# Exception(s)

`cURL error 60: SSL certificate problem...`  
cURL need an SSL certificate to communicate through **https** protocol.

## Solution 1 (recommended)

### Install an SSL certificate on your local machine
Assuming you are using WAMP on Windows:

* download an SSL certificate for your local server
https://curl.haxx.se/ca/cacert.pem  
* put it in your prefered directory (mine is `C:\Users\[MY-USERNAME]\cacert.pem`)
* edit this variable in your `php.ini` to add the path to the certificate
```ini
curl.cainfo = "C:\Users\[MY-USERNAME]\cacert.pem"
```
* restart your webserver

Now it should works, if it doesn't you might try the next solution.

## Solution 2

### Disable SSL validation (not recommended)
In `config/onesignal.php` you can add the following line in the array:
```php
return [
	// ...
	'ssl_verify'=>false
];
```