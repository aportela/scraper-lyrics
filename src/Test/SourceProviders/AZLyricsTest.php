<?php

declare(strict_types=1);

namespace aportela\ScraperLyrics\Test\SourceProviders;

require_once dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

final class AZLyricsTest extends \aportela\ScraperLyrics\Test\BaseTest
{
    #[\Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    public function testScrap(): void
    {
        $success = self::$lyrics->scrap("Bohemian Rhapsody", "Queen", [\aportela\ScraperLyrics\SourceProvider::AZLYRICS]);
        $this->assertTrue($success);
        $this->assertNotEmpty(self::$lyrics->getLyrics());
        $this->assertEquals(self::$lyrics->getSource(), "azlyrics");
    }
}
