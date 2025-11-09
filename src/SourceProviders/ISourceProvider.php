<?php

declare(strict_types=1);

namespace aportela\ScraperLyrics\SourceProviders;

interface ISourceProvider
{
    public function scrap(string $title, string $artist): string;
}
