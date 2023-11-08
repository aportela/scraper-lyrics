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
        $this->lyrics = null;
        $this->source = null;
    }

    public function __destruct()
    {
        $this->logger->debug("ScraperLyrics\Lyrics::__destruct");
    }

    public function parseTitle(string $title): string
    {
        // ugly hack to scrap "live versions"
        return (trim(preg_replace("/ \(live\)$/i", "", trim($title))));
    }

    public function parseArtist(string $artist): string
    {
        return (trim($artist));
    }

    public function scrapFromSourceProvider(string $title, string $artist, \aportela\ScraperLyrics\SourceProvider $sourceProvider): bool
    {
        $this->title = $this->parseTitle($title);
        $this->artist = $this->parseArtist($artist);
        if (!empty($this->title)) {
            if (!empty($this->artist)) {
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
                            $this->logger->debug("ScraperLyrics\Lyrics::search - Error scraping on lyricsmania: " . $e->getMessage());
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
                            echo $e->getFile() . PHP_EOL;
                            echo $e->getLine() . PHP_EOL;
                            echo $e->getMessage() . PHP_EOL;
                            $this->logger->debug("ScraperLyrics\Lyrics::search - Error scraping on genius: " . $e->getMessage());
                        }
                        break;
                    case \aportela\ScraperLyrics\SourceProvider::MUSIXMATCH:
                        $scraper = new \aportela\ScraperLyrics\SourceProviders\Musicmatch($this->logger);
                        try {
                            $this->lyrics = $scraper->scrap($this->title, $this->artist);
                            if (!empty($this->lyrics)) {
                                $this->source = "musicmatch";
                                return (true);
                            }
                        } catch (\Throwable $e) {
                            $this->logger->debug("ScraperLyrics\Lyrics::search - Error scraping on musicmatch: " . $e->getMessage());
                        }
                        break;
                    case \aportela\ScraperLyrics\SourceProvider::AZLYRICS:
                        $scraper = new \aportela\ScraperLyrics\SourceProviders\AZLyrics($this->logger);
                        try {
                            $this->lyrics = $scraper->scrap($this->title, $this->artist);
                            if (!empty($this->lyrics)) {
                                $this->source = "azlyrics";
                                return (true);
                            }
                        } catch (\Throwable $e) {
                            $this->logger->debug("ScraperLyrics\Lyrics::search - Error scraping on azlyrics: " . $e->getMessage());
                        }
                        break;
                    default:
                        return (false);
                }
            } else {
                throw new \aportela\ScraperLyrics\Exception\InvalidParamsException("artist");
            }
        } else {
            throw new \aportela\ScraperLyrics\Exception\InvalidParamsException("title");
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
                    \aportela\ScraperLyrics\SourceProvider::MUSIXMATCH,
                    \aportela\ScraperLyrics\SourceProvider::LYRICS_MANIA,
                    \aportela\ScraperLyrics\SourceProvider::GENIUS,
                    \aportela\ScraperLyrics\SourceProvider::AZLYRICS,
                ] as $provider) {
            if ($this->scrapFromSourceProvider($title, $artist, $provider)) {
                return (true);
            }
        }
        return (false);
    }
}
