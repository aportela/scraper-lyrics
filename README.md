# scraper-lyrics

Custom php lyrics scraper

## Install (composer) dependencies:

```
composer require aportela/scraper-lyrics
```

## WARNING:

Please, to prevent source providers from making changes or banning the operation of this scraper, use it reasonably, caching the results in your own storage or database to avoid repeating the same calls. Also, try not to make several calls per second that could be interpreted as a DDOS attack.

## Code example:

```
require "vendor/autoload.php";

$logger = new \Psr\Log\NullLogger("");

$lyrics = new \aportela\ScraperLyrics\Lyrics($logger);

/**
Search/Scrap on all providers
*/
if ($lyrics->scrap(
    "Bohemian Rhapsody",
    "Queen"
)) {
    echo sprintf(
        "<H1>Title: %s</h1><H2>Artist: %s</H2><H3>Source: %s</H3><PRE>%s</PRE>",
        $lyrics->title,
        $lyrics->artist,
        $lyrics->source,
        $lyrics->lyrics
    );
}

/**
    Search/Scrap on custom scrap providers
    You can use this method if at some point in the future a provider stops working and you want to save/ignore the call to its method (which will give an error) in case you previously used the global method (scrap)
*/
if ($lyrics->scrap(
    "Bohemian Rhapsody",
    "Queen",
    [
        \aportela\ScraperLyrics\SourceProvider::SEARCH_ENGINE_DUCKDUCKGO,
        \aportela\ScraperLyrics\SourceProvider::SEARCH_ENGINE_GOOGLE
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

/**
    Search/Scrap on custom source provider
    Same as the previous one but for a single source provider
*/

if ($lyrics->scrapFromSourceProvider(
    "Bohemian Rhapsody",
    "Queen",
    \aportela\ScraperLyrics\SourceProvider::SEARCH_ENGINE_DUCKDUCKGO
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
