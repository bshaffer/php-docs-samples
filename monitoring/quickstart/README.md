# Proto to JSON Demo

## Setup

1. Clone the repository
    ```
    git clone git@github.com:bshaffer/php-docs-samples.git -b proto-to-json
    ```

2. cd in to the directory
    ```
    cd monitoring/quickstart
    ```

3. Install the dependencies
    ```
    composer install
    ```

4. Change `$instanceId` from `1234567890123456789` to a Google Compute Engine (GCE)
   instance name

5. Run the script, and pass `GCLOUD_PROJECT` in as your Google Cloud project ID.
    ```
    GCLOUD_PROJECT=your-project-id php quickstart.php
    ```

## What is happening here?

This is a prototype for making HTTP 1.1 / JSON calls to Google APIs with code generated from
proto files. Some things of note:

1. [`MetricServiceJsonClient`](https://github.com/bshaffer/google-cloud-php/pull/1)
