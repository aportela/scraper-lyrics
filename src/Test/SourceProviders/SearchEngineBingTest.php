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

    public function testScrap(): void
    {
        $success = self::$lyrics->scrap("Bohemian Rhapsody", "Queen", [\aportela\ScraperLyrics\SourceProvider::SEARCH_ENGINE_BING]);
        $this->assertTrue($success);
        $this->assertNotEmpty(self::$lyrics->lyrics);
        $this->assertEquals(self::$lyrics->source, "bing");
    }
}
