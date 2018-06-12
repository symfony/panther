<?php

/*
 * This file is part of the Panthère project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Panthere\Tests\ProcessManager;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Panthere\ProcessManager\ChromeManager;
use Panthere\ProcessManager\SeleniumManager;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
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
        $co->addArguments($this->chromeMockManager->getDefaultArguments());
        $manager = new SeleniumManager('http://localhost:9515', $co->toCapabilities());
        $client = $manager->start();
        $this->assertNotEmpty($client->getCurrentURL());
        $manager->quit();
    }
}
