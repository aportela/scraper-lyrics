<?php

namespace aportela\ScraperLyrics;

class Lyrics
{
    protected \Psr\Log\LoggerInterface $logger;
    protected \aportela\HTTPRequestWrapper\HTTPRequest $http;

    // (AT THIS TIME) this is REQUIRED/IMPORTANT, with another user agents (on GOOGLE) the search response is not the same (do not include lyrics!)
    public const USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36 Edg/116.0.1938.81";

    public ?string $title;
    public ?string $artist;
    public ?string $lyrics;
    public ?string $source;

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->logger->debug("ScraperLyrics\Lyrics::__construct");
    }

    public function __destruct()
    {
        $this->logger->debug("ScraperLyrics\Lyrics::__destruct");
    }

    private function setCleanedFields(string $title, string $artist): void
    {
        $this->lyrics = null;
        $this->source = null;
        $this->title = trim($title);
        // ugly hack to scrap "live versions"
        $this->title = preg_replace("/ \(live\)$/i", "", $this->title);
        $this->artist = trim($artist);
    }

    public function scrapFromSourceProvider(string $title, string $artist, \aportela\ScraperLyrics\SourceProvider $sourceProvider): bool
    {
        $this->setCleanedFields($title, $artist);
        switch ($sourceProvider) {
            case \aportela\ScraperLyrics\SourceProvider::SEARCH_ENGINE_GOOGLE:
                $scraper = new \aportela\ScraperLyrics\SourceProviders\SearchEngineGoogle($this->logger);
                try {
                    $this->lyrics = $scraper->scrap($this->title, $this->artist);
                    if (!empty($this->lyrics)) {
                        $this->source = "google";
                        return (true);
                    }
                } catch (\Throwable $e) {
                    $this->logger->debug("ScraperLyrics\Lyrics::search - Error scraping on google search engine: " . $e->getMessage());
                }
                break;
            case \aportela\ScraperLyrics\SourceProvider::SEARCH_ENGINE_BING:
                $scraper = new \aportela\ScraperLyrics\SourceProviders\SearchEngineBing($this->logger);
                try {
                    $this->lyrics = $scraper->scrap($this->title, $this->artist);
                    if (!empty($this->lyrics)) {
                        $this->source = "bing";
                        return (true);
                    }
                } catch (\Throwable $e) {
                    $this->logger->debug("ScraperLyrics\Lyrics::search - Error scraping on bing search engine: " . $e->getMessage());
                }
                break;
            case \aportela\ScraperLyrics\SourceProvider::SEARCH_ENGINE_DUCKDUCKGO:
                $scraper = new \aportela\ScraperLyrics\SourceProviders\SearchEngineDuckDuckGo($this->logger);
                try {
                    $this->lyrics = $scraper->scrap($this->title, $this->artist);
                    if (!empty($this->lyrics)) {
                        $this->source = "duckduckgo";
                        return (true);
                    }
                } catch (\Throwable $e) {
                    $this->logger->debug("ScraperLyrics\Lyrics::search - Error scraping on duckduckgo search engine: " . $e->getMessage());
                }
                break;
            case \aportela\ScraperLyrics\SourceProvider::LYRICS_MANIA:
                $scraper = new \aportela\ScraperLyrics\SourceProviders\LyricsMania($this->logger);
                try {
                    $this->lyrics = $scraper->scrap($this->title, $this->artist);
                    if (!empty($this->lyrics)) {
                        $this->source = "lyricsmania";
                        return (true);
                    }
                } catch (\Throwable $e) {
                    $this->logger->debug("ScraperLyrics\Lyrics::search - Error scraping on lyricsmania search engine: " . $e->getMessage());
                }
                break;
            case \aportela\ScraperLyrics\SourceProvider::GENIUS:
                $scraper = new \aportela\ScraperLyrics\SourceProviders\Genius($this->logger);
                try {
                    $this->lyrics = $scraper->scrap($this->title, $this->artist);
                    if (!empty($this->lyrics)) {
                        $this->source = "genius";
                        return (true);
                    }
                } catch (\Throwable $e) {
                    $this->logger->debug("ScraperLyrics\Lyrics::search - Error scraping on genius search engine: " . $e->getMessage());
                }
                break;
            default:
                return (false);
                break;
        }
        return (false);
    }

    public function scrap(string $title, string $artist, ?array $providers = []): bool
    {
        foreach (
            (isset($providers) && is_array($providers) && count($providers) > 0) ?
                $providers :
                [
                    \aportela\ScraperLyrics\SourceProvider::SEARCH_ENGINE_DUCKDUCKGO,
                    \aportela\ScraperLyrics\SourceProvider::SEARCH_ENGINE_GOOGLE,
                    \aportela\ScraperLyrics\SourceProvider::SEARCH_ENGINE_BING,
                    \aportela\ScraperLyrics\SourceProvider::LYRICS_MANIA,
                    \aportela\ScraperLyrics\SourceProvider::GENIUS,
                ] as $provider) {
            if ($this->scrapFromSourceProvider($title, $artist, $provider)) {
                return (true);
            }
        }
        return (false);
    }
}
