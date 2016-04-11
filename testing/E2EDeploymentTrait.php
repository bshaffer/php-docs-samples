<?php
/*
 * Copyright 2015 Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Cloud\Test;

/**
 * Class E2EDeploymentTrait
 * @package Google\Cloud\Samples\Bookshelf
 *
 * Use this trait to deploy the project to GCP for an end-to-end test.
 */
trait E2EDeploymentTrait
{
    const PROJECT_ENV = 'GOOGLE_PROJECT_ID';
    const VERSION_ENV = 'GOOGLE_VERSION_ID';
    const DB_PASSWORD_ENV = 'MYSQLADMIN_ROOT_PASSWORD';
    const BF_SECRET_ENV = 'BLOWFISH_SECRET';
    const CLOUDSQL_INSTANCE_ENV = 'PHPMYADMIN_CLOUDSQL_INSTANCE';

    private $client;

    private static function output($line)
    {
        fwrite(STDERR, $line . "\n");
    }

    private static function delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file") && !is_link($dir)) ?
                self::delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    private function downloadPhpmyadmin($dir)
    {
        $tmp = sys_get_temp_dir();
        $url = 'https://files.phpmyadmin.net/phpMyAdmin/'
            . self::PHPMYADMIN_VERSION . '/phpMyAdmin-'
            . self::PHPMYADMIN_VERSION . '-all-languages.tar.bz2';
        $tmpdir = substr(basename($url), 0, -8);
        $file = $tmp . DIRECTORY_SEPARATOR . basename($url);
        file_put_contents($file, file_get_contents($url));
        $phar = new \PharData($file, 0, null);
        $result = $phar -> extractTo($tmp, null, true);
        rename($tmp . DIRECTORY_SEPARATOR . $tmpdir, $dir);
        unlink($file);
    }

    abstract static protected function copyFiles($targetDir);
    abstract static protected function getVersion();
    abstract static protected function getTargetDir();

    public static function copyFiles($files, $params)
    {
        $loader = new \Twig_Loader_Filesystem(__DIR__ . '/../');
        $twig = new \Twig_Environment($loader);

        foreach ($files as $file) {
            $dest = $target . DIRECTORY_SEPARATOR . $file;
            touch($dest);
            chmod($dest, 0640);
            $content = $twig->render($file, $params);
            file_put_contents($dest, $content, LOCK_EX);
        }
    }

    public static function getProjectId()
    {
        $project_id = getenv(self::PROJECT_ENV);

        if ($project_id === false) {
            self::fail('Please set ' . self::PROJECT_ENV . ' env var.');
        }

        return $project_id;
    }

    public static function deploy($project_id, $e2e_test_version, $target)
    {
        $command = "gcloud -q preview app deploy --no-promote "
            . "--no-stop-previous-version "
            . "--version $e2e_test_version "
            . "--project $project_id "
            . "$target/app.yaml";
        for ($i = 0; $i <= 3; $i++) {
            exec($command, $output, $ret);
            foreach ($output as $line) {
                self::output($line);
            }
            if ($ret === 0) {
                return;
            } else {
                self::output('Retrying deployment');
            }
        }
        self::fail('Deployment failed.');
    }


    public static function tearDownAfterClass()
    {
        $command = 'gcloud -q preview app modules delete phpmyadmin --version '
            . getenv(self::VERSION_ENV)
            . ' --project '
            . getenv(self::PROJECT_ENV);
        exec($command, $output, $ret);
        foreach ($output as $line) {
            self::output($line);
        }
        if ($ret === 0) {
            self::output('Successfully delete the version');
            return;
        } else {
            self::output('Retrying to delete the version');
        }
        self::fail('Failed to delete the version.');
    }
}
