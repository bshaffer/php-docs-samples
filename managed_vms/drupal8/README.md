Drupal 8 on Managed VMs
=======================

## Overview

This guide will help you deploy Drupal 8 on [App Engine Managed VMs][1]

## Prerequisites

Before setting up Drupal 8 on Managed VMs, you will need to complete the following:

  1. Create a [Google Cloud Platform project][2]. Note your **Project ID**, as you will need it
     later.
  1. Create a [Google Cloud SQL instance][3]. You will use this as your Drupal MySQL backend.

## Install Drupal 8

### Download

Use the [Drupal 8 Console][4] to install a drupal project with the following command:

```sh
drupal site:new PROJECT_NAME 8.0.0
```

Alternatively, you can download a compressed file from the [Drupal Website][5].

### Installation

  1. Set up your Drupal 8 instance using the web interface
  ```sh
  cd /path/to/drupal
  php -S localhost:8080
  ```
  Open [http://localhost:8080](http://localhost:8080) in your browser after running these steps

  1. **BETA** You can also try setting up your Drupal 8 instance using the [Drupal 8 Console][4]
  ```sh
  cd /path/to/drupal
  drupal site:install \
    --langcode en \
    --db-type mysql \
    --db-name DATABASE_NAME
    --db-user DATABASE_USERNAME
    --db-pass DATABASE_PASSWORD
    --site-name 'My Drupal Site On Google' \
    --site-mail you@example.com \
    --account-name admin \
    --account-mail you@example.com \
    --account-pass admin
  ```

You will want to use the Cloud SQL credentials you created in the **Prerequisites** section as your
Drupal backend.

## Copy over App Engine files

For your app to deploy on App Engine Managed VMs, you will need to copy over the files in this
directory:

```sh
# clone this repo somewhere
git clone https://github.com/GoogleCloudPlatform/php-docs-samples /path/to/php-docs-samples

# copy the four files below to the root directory of your Drupal project
cd /path/to/php-docs-samples/managed_vms/drupal8/
cp ./{app.yaml,php.ini,Dockerfile,nginx-app.conf} /path/to/drupal
```

The four files needed are as follows:

  1. [`app.yaml`](app.yaml) - The App Engine configuration for your project
  1. [`Dockerfile`](Dockerfile) - Container configuration for the PHP runtime
  1. [`php.ini`](php.ini) - Optional ini used to extend the runtime configuration.
  1. [`nginx-app.conf`](nginx-app.conf) - Nginx web server configuration needed for `Drupal 8`

[1]: https://cloud.google.com/appengine/docs/managed-vms/
[2]: https://console.cloud.google.com
[3]: https://cloud.google.com/sql/docs/getting-started
[4]: https://www.drupal.org/project/console
[5]: https://www.drupal.org/drupal-8.0.4-release-notes