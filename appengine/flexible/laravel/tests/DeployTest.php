<?php
/**
 * Copyright 2016 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Cloud\Samples\AppEngine\Symfony;

use Google\Cloud\TestUtils\ExecuteCommandTrait;
use Symfony\Component\Yaml\Yaml;
use Monolog\Logger;
use GuzzleHttp\Client;

class DeployTest extends \PHPUnit_Framework_TestCase
{
    use ExecuteCommandTrait;

    private $client;
    private static $version;

    private static function getVersion()
    {
        if (is_null(self::$version)) {
            $versionId = getenv('GOOGLE_VERSION_ID') ?: time();
            self::$version = "laravel-" . $versionId;
        }

        return self::$version;
    }

    public static function getProjectId()
    {
        return getenv('GOOGLE_PROJECT_ID');
    }

    public static function getServiceName()
    {
        return getenv('GOOGLE_SERVICE_NAME');
    }

    private static function getTargetDir()
    {
        $tmp = sys_get_temp_dir();
        $versionId = self::getVersion();
        $targetDir = sprintf('%s/%s', $tmp, $versionId);

        if (!file_exists($targetDir)) {
            mkdir($targetDir);
        }

        if (!is_writable($targetDir)) {
            throw new \Exception(sprintf('Cannot write to %s', $targetDir));
        }

        return $targetDir;
    }

    public static function setUpBeforeClass()
    {
        if (getenv('RUN_DEPLOYMENT_TESTS') !== 'true') {
            self::markTestSkipped(
                'To run this test, set RUN_DEPLOYMENT_TESTS env to "true".'
            );
        }

        self::$logger = new Logger('phpunit');

        // verify and set environment variables
        self::verifyEnvironmentVariables();
        $targetDir = self::getTargetDir();
        $projectId = self::getProjectId();
        $version = self::getVersion();

        // move into the target directory
        self::setWorkingDirectory($targetDir);
        self::createLaravelProject($targetDir);
        self::addPostBuildCommands($targetDir);
        self::deploy($projectId, $version, $targetDir);
    }

    private static function verifyEnvironmentVariables()
    {
        $envVars = [
            'GOOGLE_PROJECT_ID'
        ];
        foreach ($envVars as $envVar) {
            if (false === getenv($envVar)) {
                self::fail("Please set the ${envVar} environment variable");
            }
        }
    }

    private static function createLaravelProject($targetDir)
    {
        // install
        $laravelVersion = 'laravel/laravel';
        $cmd = sprintf('composer create-project --no-scripts %s %s', $laravelVersion, $targetDir);
        $process = self::createProcess($cmd);
        $process->setTimeout(300); // 5 minutes
        self::executeProcess($process);

        // move the code for the sample to the new drupal installation
        $files = ['app.yaml', 'nginx-app.conf'];
        foreach ($files as $file) {
            $source = sprintf('%s/../%s', __DIR__, $file);
            $target = sprintf('%s/%s', $targetDir, $file);
            copy($source, $target);
        }

        // if a service name has been defined, add it to "app.yaml"
        if ($service = self::getServiceName()) {
            $appYaml = sprintf('%s/app.yaml', $targetDir);
            file_put_contents($appYaml, "service: $service\n", FILE_APPEND);
        }
    }

    private static function addPostBuildCommands($targetDir)
    {
        self::execute(sprintf('php %s/../add_composer_scripts.php %s/composer.json',
            __DIR__,
            $targetDir
        ));
    }

    public static function deploy($projectId, $versionId, $targetDir)
    {
        for ($i = 0; $i <= 3; $i++) {
            $process = self::createProcess(
                "gcloud -q app deploy "
                . "--version $versionId "
                . "--project $projectId --no-promote -q "
                . "$targetDir/app.yaml"
            );
            $process->setTimeout(60 * 30); // 30 minutes
            if (self::executeProcess($process, false)) {
                return;
            }
            self::$logger->warning('Retrying deployment');
        }
        self::fail('Deployment failed.');
    }

    public static function tearDownAfterClass()
    {
        for ($i = 0; $i <= 3; $i++) {
            $process = self::createProcess(sprintf(
                'gcloud -q app versions delete %s --service %s --project %s',
                self::getVersion(),
                self::getServiceName() ?: 'default',
                self::getProjectId()
            ));
            $process->setTimeout(600); // 10 minutes
            if (self::executeProcess($process, false)) {
                return;
            }
            self::$logger->warning('Retrying to delete the version');
        }

        // remove the tmp directory
        self::execute('rm -Rf ' . $this->getTargetDir());
    }

    public function setUp()
    {
        $service = self::getServiceName();
        $url = sprintf('https://%s%s-dot-%s.appspot.com/',
            self::getVersion(),
            $service ? "-dot-$service" : '',
            self::getProjectId());
        $this->client = new Client(['base_uri' => $url]);
    }

    public function testHomepage()
    {
        // Access the blog top page
        $resp = $this->client->get('/');
        $this->assertEquals(
            '200',
            $resp->getStatusCode(),
            'top page status code'
        );
        $content = $resp->getBody()->getContents();
        $this->assertContains('Laravel', $content);
    }
}
