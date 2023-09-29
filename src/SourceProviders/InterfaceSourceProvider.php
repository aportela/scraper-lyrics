<?php

namespace aportela\ScraperLyrics\SourceProviders;

interface InterfaceSourceProvider
{
    public function scrap(string $title, string $artist): string;
}
