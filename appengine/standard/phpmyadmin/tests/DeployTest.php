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
namespace Google\Cloud\Test;

use GuzzleHttp\Client;

class DeployTest extends \PHPUnit_Framework_TestCase
{
    const PHPMYADMIN_VERSION = '4.5.5.1';

    protected static function getTargetDir()
    {
        $tmp = sys_get_temp_dir();
        $version = self::getVersion();
        $ret = "$tmp/phpmyadmin-test-$version";
        if (is_file($ret)) {
            self::fail("$ret is a normal file and can not proceed.");
        }
        if (is_dir($ret)) {
            self::delTree($ret);
        }
        mkdir($ret, 0750, true);
        return realpath($ret);
    }

    public static function setUpBeforeClass()
    {
        $version = self::getVersion();
        $project_id = getenv(self::PROJECT_ENV);
        $blowfish_secret = getenv(self::BF_SECRET_ENV);
        $cloudsql_instance = getenv(self::CLOUDSQL_INSTANCE_ENV);
        $db_password = getenv(self::DB_PASSWORD_ENV);
        if ($version === false) {
            self::fail('Please set ' . self::VERSION_ENV . ' env var.');
        }
        if ($blowfish_secret === false) {
            self::fail('Please set ' . self::BF_SECRET_ENV . ' env var.');
        }
        if ($cloudsql_instance === false) {
            self::fail(
                'Please set ' . self::CLOUDSQL_INSTANCE_ENV . ' env var.');
        }
        if ($db_password === false) {
            self::fail('Please set ' . self::DB_PASSWORD_ENV . ' env var.');
        }

        $files = [
            'app-e2e.yaml' => $target,
            'php.ini' => $target,
            'config.inc.php' => $target,
        ];

        $params = [
            'your_project_id' => $project_id,
            'your_secret' => $blowfish_secret,
            'your_cloudsql_instance' => $cloudsql_instance
        ];

        self::downloadPhpmyadmin($target);
        self::copyFiles($files, $params);
        rename("$target/app-e2e.yaml", "$target/app.yaml");
        self::deploy($project_id, $version, $target);
    }

    public function setUp()
    {
        $url = sprintf('https://%s-dot-phpmyadmin-dot-%s.appspot.com/',
                       getenv(self::VERSION_ENV),
                       getenv(self::PROJECT_ENV));
        $this->client = new Client(['base_uri' => $url]);
    }

    public function testIndex()
    {
        // Index serves succesfully the login screen.
        $resp = $this->client->get('');
        $this->assertEquals('200', $resp->getStatusCode(),
                            'Login screen status code');
        // TODO check the contents
    }
}
