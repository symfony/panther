<?php

/*
 * This file is part of the Panther project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Component\Panther\Tests\ProcessManager;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\ProcessManager\ChromeManager;
use Symfony\Component\Panther\ProcessManager\WebServerManager;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ChromeManagerTest extends TestCase
{
    public function testRun()
    {
        $manager = new ChromeManager();
        $client = $manager->start();
        $this->assertNotEmpty($client->getCurrentURL());
        $manager->quit();
    }

    public function testAlreadyRunning()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The port 9515 is already in use.');

        $driver1 = new ChromeManager();
        $driver1->start();

        $driver2 = new ChromeManager();
        try {
            $driver2->start();
        } finally {
            $driver1->quit();
        }
    }

    public function testNonDefaultPort()
    {
        $manager = new ChromeManager(null, null, ['port' => 9516]);
        $client = $manager->start();
        $this->assertNotEmpty($client->getCurrentURL());
        $manager->quit();
    }

    public function testMultipleInstances()
    {
        $driver1 = new ChromeManager();
        $client1 = $driver1->start();

        $driver2 = new ChromeManager(null, null, ['port' => 9516]);
        $client2 = $driver2->start();

        $this->assertNotEmpty($client1->getCurrentURL());
        $this->assertNotEmpty($client2->getCurrentURL());

        $driver1->quit();
        $driver2->quit();
    }

    public function testExperimentalOptions()
    {
        $userAgent = 'Mozilla/5.0 (Linux; Android 4.2.1; en-us; Nexus 5 Build/JOP40D) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.166 Mobile Safari/535.19';

        $deviceMetrics = new \stdClass();
        $deviceMetrics->width = 360;
        $deviceMetrics->height = 640;
        $deviceMetrics->pixelRatio = 3.0;

        $mobileEmulation = new \stdClass();
        $mobileEmulation->{'deviceMetrics'} = $deviceMetrics;
        $mobileEmulation->{'userAgent'} = $userAgent;

        $experimentalOptions = [
            'mobileEmulation' => $mobileEmulation,
        ];

        $server = new WebServerManager(__DIR__.'/../fixtures/', '127.0.0.1', 1234);
        $server->start();

        $client = Client::createChromeClient(null, null, [
            'port' => 9516,
            'experimentalOptions' => $experimentalOptions,
        ]);
        $crawler = $client->request('GET', 'http://127.0.0.1:1234/user-agent.php');
        $expectedUserAgent = $crawler->filter('body')->getText();
        $client->quit();

        $server->quit();

        $this->assertSame($expectedUserAgent, $userAgent);
    }

    public function testLoggingPrefs()
    {
        $logType = 'performance';
        $loggingPrefs = new \stdClass();
        $loggingPrefs->{$logType} = 'ALL';

        $server = new WebServerManager(__DIR__.'/../fixtures/', '127.0.0.1', 1234);
        $server->start();

        $driver = new ChromeManager(null, null, [
            'port' => 9516,
            'capabilities' => [
                'goog:loggingPrefs' => $loggingPrefs,
            ],
        ]);
        $webDriver = $driver->start();
        $webDriver->get('http://127.0.0.1:1234/link.html');
        $logs = $webDriver->manage()->getLog($logType);
        $webDriver->quit();

        $server->quit();

        $this->assertNotEmpty($logs);
        foreach ($logs as $log) {
            $this->assertArrayHasKey('level', $log);
            $this->assertArrayHasKey('message', $log);
            $this->assertArrayHasKey('timestamp', $log);
        }
    }

    public function testExtensions()
    {
        $arguments = [
            // Headless mode doesn't currently support extensions.
            // See https://bugs.chromium.org/p/chromium/issues/detail?id=706008
        ];
        $driver = new ChromeManager(null, $arguments, [
            'port' => 9517,
            'extensions' => [
                __DIR__.'/../fixtures/hello-world.crx',
            ],
        ]);
        $webDriver = $driver->start();
        $webDriver->get('chrome://extensions/?id=lkihgibmkalpkacicpnkpneenbdaoogp');
        $title = $webDriver->getTitle();
        $webDriver->quit();

        $this->assertSame($title, 'Extensions - Hello World');
    }
}
