<?php

declare(strict_types=1);

namespace aportela\ScraperLyrics\Test;

require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

final class LyricsTest extends BaseTest
{
    protected static $lyrics;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$lyrics = new \aportela\ScraperLyrics\Lyrics(self::$logger);
    }

    public function testScrapWithoutSourceProviders(): void
    {
        $this->assertFalse(self::$lyrics->scrap("", "", []));
    }
}
