<?php

declare(strict_types=1);

namespace aportela\ScraperLyrics\Test\SourceProviders;

require_once dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

final class GeniusTest extends \aportela\ScraperLyrics\Test\BaseTest
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    public function testScrap(): void
    {
        $success = self::$lyrics->scrap("Bohemian Rhapsody", "Queen", [\aportela\ScraperLyrics\SourceProvider::GENIUS]);
        $this->assertTrue($success);
        $this->assertNotEmpty(self::$lyrics->getLyrics());
        $this->assertEquals(self::$lyrics->getSource(), "genius");
    }
}
