<?php

declare(strict_types=1);

namespace aportela\ScraperLyrics\Test\SourceProviders;

require_once dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

final class SearchEngineGoogleTest extends \aportela\ScraperLyrics\Test\BaseTest
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    public function testScrapSuccess(): void
    {
        $success = self::$lyrics->scrap("Bohemian Rhapsody", "Queen", [\aportela\ScraperLyrics\SourceProvider::SEARCH_ENGINE_GOOGLE]);
        $this->assertTrue($success);
        $this->assertNotEmpty(self::$lyrics->lyrics);
        $this->assertEquals(self::$lyrics->source, "google");
    }

    public function testScrapNotFound(): void
    {
        $this->assertFalse(self::$lyrics->scrap("#", "#", [\aportela\ScraperLyrics\SourceProvider::SEARCH_ENGINE_GOOGLE]));
    }
}
