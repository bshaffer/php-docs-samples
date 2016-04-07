<?php
/**
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

require_once __DIR__ . '/../vendor/autoload.php';

use Silex\WebTestCase;

class mailjetTest extends WebTestCase
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../app.php';

        // set some parameters for testing
        $app['session.test'] = true;
        $app['debug'] = true;
        $projectId = getenv('GOOGLE_PROJECT_ID');

        // set your Mailjet API key and secret
        $mailjetApiKey = getenv('MAILJET_APIKEY');
        $mailjetSecret = getenv('MAILJET_SECRET');

        if (empty($mailjetApiKey) || empty($mailjetSecret)) {
            $this->markTestSkipped('set the MAILJET_APIKEY and MAILJET_SECRET environment variables');
        }

        $app['mailjet.api_key'] = $mailjetApiKey;
        $app['mailjet.secret'] = $mailjetSecret;

        // prevent HTML error exceptions
        unset($app['exception_handler']);

        return $app;
    }

    public function testHome()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/');

        $this->assertTrue($client->getResponse()->isOk());
    }

    public function testSendEmail()
    {
        $client = $this->createClient();

        $crawler = $client->request('POST', '/send', [
            'recipient' => 'fake@example.com',
        ]);

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('"Sent"', $response->getContent());
        $this->assertContains('"fake@example.com"', $response->getContent());
    }

    public function testStatistics()
    {
        // create an email and then get info about it
        $body = [
            'FromEmail' => "betterbrent@google.com",
            'Subject' => "Email from unit tests",
            'Text-part' => "Unit tests for mailjet",
            'Recipients' => [['Email' => 'test@example.com']]
        ];

        $mailjet = $this->app['mailjet'];

        $response = $mailjet->post(Mailjet\Resources::$Email, ['body' => $body]);
        $this->assertTrue($response->success());

        $data = $response->getData();
        $this->assertArrayHasKey('Sent', $data);
        $this->assertArrayHasKey(0, $data['Sent']);
        $this->assertArrayHasKey('MessageID', $data['Sent'][0]);

        $client = $this->createClient();
        $crawler = $client->request('POST', '/stats', [
            'message_id' => $data['Sent'][0]['MessageID'],
        ]);

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('"Sent"', $response->getContent());
        $this->assertContains('"fake@example.com"', $response->getContent());
    }
}
