# Proto to JSON Demo

## What is happening here?

This is a simple app for benchmarking HTTP 1.1 / JSON calls vs gRPC.

## Setup

1. Clone the repository
    ```
    git clone git@github.com:bshaffer/php-docs-samples.git -b proto-to-json
    ```

2. cd in to the directory
    ```
    cd monitoring/benchmark
    ```

3. Install the dependencies
    ```
    composer install
    ```

5. Use Apache Bench to benchmark the deployed apps
    ```
    ab -c 5 -n 200 https://YOUR_PROJECT_ID.appspot.com/grpc
    ab -c 5 -n 200 https://YOUR_PROJECT_ID.appspot.com/json
    ```
