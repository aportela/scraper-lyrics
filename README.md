# scraper-lyrics

Custom php lyrics scraper

## Install (composer) dependencies:

```
composer require aportela/scraper-lyrics
```

## Code example:

```
require "vendor/autoload.php";

$logger = new \Psr\Log\NullLogger("");

$lyrics = new \aportela\ScraperLyrics\Lyrics($logger);

if ($lyrics->scrap(
    "Bohemian Rhapsody",
    "Queen",
    [
        \aportela\ScraperLyrics\SourceProvider::SEARCH_ENGINE_DUCKDUCKGO,
        \aportela\ScraperLyrics\SourceProvider::SEARCH_ENGINE_GOOGLE,
        \aportela\ScraperLyrics\SourceProvider::SEARCH_ENGINE_BING
    ]
)) {
    echo sprintf(
        "<H1>Title: %s</h1><H2>Artist: %s</H2><H3>Source: %s</H3><PRE>%s</PRE>",
        $lyrics->title,
        $lyrics->artist,
        $lyrics->source,
        $lyrics->lyrics
    );
}
```

![PHP Composer](https://github.com/aportela/scraper-lyrics/actions/workflows/php.yml/badge.svg)
