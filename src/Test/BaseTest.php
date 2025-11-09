<?php

declare(strict_types=1);

namespace aportela\ScraperLyrics\Test;

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

abstract class BaseTest extends \PHPUnit\Framework\TestCase
{
    protected static \Psr\Log\LoggerInterface $logger;

    protected static \aportela\ScraperLyrics\Lyrics $lyrics;

    protected static string $cachePath;

    protected static \aportela\SimpleFSCache\Cache $cache;

    /**
     * Called once just like normal constructor
     */
    public static function setUpBeforeClass(): void
    {
        self::$logger = new \Psr\Log\NullLogger();
        self::$cache = new \aportela\SimpleFSCache\Cache(self::$logger, dirname(__FILE__, 3) . DIRECTORY_SEPARATOR . "cache", null, \aportela\SimpleFSCache\CacheFormat::TXT);
        self::$lyrics = new \aportela\ScraperLyrics\Lyrics(self::$logger, self::$cache);
    }

    /**
     * Initialize the test case
     * Called for every defined test
     */
    protected function setUp(): void
    {
    }

    /**
     * Clean up the test case, called for every defined test
     */
    protected function tearDown(): void
    {
    }

    /**
     * Clean up the whole test class
     */
    public static function tearDownAfterClass(): void
    {
    }
}
