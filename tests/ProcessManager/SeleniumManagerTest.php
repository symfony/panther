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

use Facebook\WebDriver\Chrome\ChromeOptions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Panther\ProcessManager\ChromeManager;
use Symfony\Component\Panther\ProcessManager\SeleniumManager;

/**
 * @author Dmitry Kuzmin <rockwith@me.com>
 */
class SeleniumManagerTest extends TestCase
{
    /**
     * we can mock selenium with built-in ChromeManager.
     *
     * @var ChromeManager
     */
    protected $chromeMockManager;

    public function setUp(): void
    {
        $this->chromeMockManager = new ChromeManager();
        $this->chromeMockManager->start();
    }

    public function tearDown(): void
    {
        $this->chromeMockManager->quit();
    }

    public function testRun()
    {
        $co = new ChromeOptions();
        $co->addArguments(['--headless', 'window-size=1200,1100', '--disable-gpu', '--no-sandbox']);
        $manager = new SeleniumManager('http://localhost:9515', $co->toCapabilities());
        $client = $manager->start();
        $this->assertNotEmpty($client->getCurrentURL());
        $manager->quit();
    }
}
