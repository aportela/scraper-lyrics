<?php

declare(strict_types=1);

namespace aportela\ScraperLyrics\Test\SourceProviders;

require_once dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

final class SearchEngineBingTest extends \aportela\ScraperLyrics\Test\BaseTest
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    public function testScrapSuccess(): void
    {
        $success = self::$lyrics->scrap("Bohemian Rhapsody", "Queen", [\aportela\ScraperLyrics\SourceProvider::SEARCH_ENGINE_BING]);
        // I suspect that sometimes BING fail scrap on first call (next are working)
        // At this time, I don't know if it is due to some protection method of the server itself or if it is waiting for some type of previous redirection / cookie set
        // I tried getting cookies with HEAD (Previous to GET), sending Referer header, faking User Agent to Edge, nothing works 100%
        // ... SO retry scrap if first failed... (I know, this sucks)
        if (!$success) {
            $success = self::$lyrics->scrap("Bohemian Rhapsody", "Queen", [\aportela\ScraperLyrics\SourceProvider::SEARCH_ENGINE_BING]);
        }
        $this->assertTrue($success);
        $this->assertNotEmpty(self::$lyrics->lyrics);
        $this->assertEquals(self::$lyrics->source, "bing");
    }

    public function testScrapNotFound(): void
    {
        $this->assertFalse(self::$lyrics->scrap("#", "#", [\aportela\ScraperLyrics\SourceProvider::SEARCH_ENGINE_BING]));
    }
}
