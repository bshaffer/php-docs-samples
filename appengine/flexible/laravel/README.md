Laravel on App Engine Flexible Environment
==========================================

## Overview

This guide will help you deploy Laravel on [App Engine Flexible Environment][1]

## Prerequisites

Before setting up Laravel on App Engine, you will need to complete the following:

  1. Create a [Google Cloud Platform project][2]. Note your **Project ID**, as you will need it
     later.

## Install Laravel

Use composer to download Laravel and its dependencies

```sh
composer create-project laravel/laravel
```

## Copy over App Engine files

For your app to deploy on App Engine Flexible, you will need to copy over the files in this
directory:

```sh
# clone this repo somewhere
git clone https://github.com/GoogleCloudPlatform/php-docs-samples /path/to/php-docs-samples

# copy the two files below to the root directory of your Laravel project
cd /path/to/php-docs-samples/appengine/flexible/laravel/
cp ./{app.yaml,nginx-app.conf} /path/to/laravel
```

The two files needed are as follows:

  1. [`app.yaml`](app.yaml) - The App Engine configuration for your project
  1. [`nginx-app.conf`](nginx-app.conf) - Nginx web server configuration needed for `Laravel`

Next, you need to have a few scripts run after your application deploys.
You can do this by providing the path to your `composer.json` to the `add_composer_scripts.php`
script:

```sh
php ./add_composer_scripts.php /path/to/laravel/composer.json
```
This will add the following scripts to your project's `composer.json`:

```json
{
    "scripts": {
        "post-deploy-cmd": [
            "chmod -R 755 bootstrap\/cache",
            "php artisan optimize",
            "echo \"MEMCACHED_HOST=$MEMCACHE_PORT_11211_TCP_ADDR\" >> .env",
            "echo \"MEMCACHED_PORT=$MEMCACHE_PORT_11211_TCP_PORT\" >> .env"
        ]
    }
}
```

[1]: https://cloud.google.com/appengine/docs/flexible/
[2]: https://console.cloud.google.com
