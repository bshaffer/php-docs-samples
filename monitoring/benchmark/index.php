<?php
/**
 * Copyright 2017 Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

# Includes the autoloader for libraries installed with composer
require_once __DIR__ . '/vendor/autoload.php';

# Imports the Google Cloud client library
use Google\Api\Metric;
use Google\Api\MonitoredResource;
use Google\Cloud\Monitoring\V3\MetricServiceClient;
use Google\Monitoring\V3\MetricServiceJsonClient;
use Google\Monitoring\V3\Point;
use Google\Monitoring\V3\TimeInterval;
use Google\Monitoring\V3\TimeSeries;
use Google\Monitoring\V3\TypedValue;
use Google\Protobuf\Timestamp;

# Silex Stuff
use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// create the Silex application
$app = new Application();
$app['debug'] = true;

$app->get('/grpc', function (Application $app, Request $request) {
    createTimeSeries(new MetricServiceClient());
    return new Response('Successfully submitted a time series using GRPC');
});
$app->get('/json', function (Application $app, Request $request) {
    createTimeSeries(new MetricServiceClient([
        'createMetricServiceStubFunction' => function ($hostname, $opts) {
            return new MetricServiceJsonClient($hostname, $opts);
        }
    ]));
    return new Response('Successfully submitted a time series using JSON');
});

$app->run();

function createTimeSeries(MetricServiceClient $client)
{
    // These variables are set by the App Engine environment. To test locally,
    // ensure these are set or manually change their values.
    $projectId = getenv('GCLOUD_PROJECT') ?: 'YOUR_PROJECT_ID';
    $instanceId = 'instance-1';
    $zone = 'us-central1-f';
    try {
        $formattedProjectName = MetricServiceClient::formatProjectName($projectId);
        $labels = [
            'instance_id' => $instanceId,
            'zone' => $zone,
        ];

        $m = new Metric();
        $m->setType('custom.googleapis.com/my_metric');

        $r = new MonitoredResource();
        $r->setType('gce_instance');
        $r->setLabels($labels);

        $value = new TypedValue();
        $value->setDoubleValue(3.14);

        $timestamp = new Timestamp();
        $timestamp->setSeconds(time());

        $interval = new TimeInterval();
        $interval->setStartTime($timestamp);
        $interval->setEndTime($timestamp);

        $point = new Point();
        $point->setValue($value);
        $point->setInterval($interval);
        $points = [$point];

        $timeSeries = new TimeSeries();
        $timeSeries->setMetric($m);
        $timeSeries->setResource($r);
        $timeSeries->setPoints($points);

        $client->createTimeSeries($formattedProjectName, [$timeSeries]);
    } finally {
        $client->close();
    }
}
