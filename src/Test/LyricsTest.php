<?php

declare(strict_types=1);

namespace aportela\ScraperLyrics\Test;

require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

final class LyricsTest extends BaseTest
{
    public function testScrapWithoutTitle(): void
    {
        $this->expectException(\aportela\ScraperLyrics\Exception\InvalidParamsException::class);
        $this->expectExceptionMessage("title");
        self::$lyrics->scrap("", "");
    }

    public function testScrapWithoutArtist(): void
    {
        $this->expectException(\aportela\ScraperLyrics\Exception\InvalidParamsException::class);
        $this->expectExceptionMessage("artist");
        self::$lyrics->scrap("song title", "");
    }

    public function testScrap(): void
    {
        $success = self::$lyrics->scrap("Bohemian Rhapsody", "Queen");
        $this->assertTrue($success);
        $this->assertNotEmpty(self::$lyrics->source);
        $this->assertNotEmpty(self::$lyrics->lyrics);
    }
}
