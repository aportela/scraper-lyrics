<?php

declare(strict_types=1);

namespace aportela\ScraperLyrics\Test;

require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

class BaseTest extends \PHPUnit\Framework\TestCase
{
    protected static \Psr\Log\LoggerInterface $logger;
    protected static \aportela\ScraperLyrics\Lyrics $lyrics;


    /**
     * Called once just like normal constructor
     */
    public static function setUpBeforeClass(): void
    {
        self::$logger = new \Psr\Log\NullLogger();
        self::$lyrics = new \aportela\ScraperLyrics\Lyrics(self::$logger);
    }

    /**
     * Initialize the test case
     * Called for every defined test
     */
    public function setUp(): void
    {
    }

    /**
     * Clean up the test case, called for every defined test
     */
    public function tearDown(): void
    {
    }

    /**
     * Clean up the whole test class
     */
    public static function tearDownAfterClass(): void
    {
    }
}
