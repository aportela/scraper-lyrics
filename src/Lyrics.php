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

    public function scrap(string $title, string $artist, array $providers = []): bool
    {
        $this->lyrics = null;
        $this->source = null;
        $this->title = trim($title);
        // ugly hack to scrap "live versions"
        $this->title = preg_replace("/ \(live\)$/i", "", $this->title);
        $this->artist = trim($artist);
        foreach ($providers as $provider) {
            switch ($provider) {
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
            }
        }
        return (false);
    }
}
