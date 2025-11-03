<?php

namespace aportela\ScraperLyrics;

class Lyrics
{
    protected \Psr\Log\LoggerInterface $logger;
    protected \aportela\HTTPRequestWrapper\HTTPRequest $http;
    protected ?\aportela\SimpleFSCache\Cache $cache = null;

    // (AT THIS TIME) this is REQUIRED/IMPORTANT, with another user agents (on GOOGLE) the search response is not the same (do not include lyrics!)
    public const USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36 Edg/116.0.1938.81";

    public ?string $title;
    public ?string $artist;
    public ?string $lyrics;
    public ?string $source;

    public function __construct(\Psr\Log\LoggerInterface $logger, ?\aportela\SimpleFSCache\Cache $cache = null)
    {
        $this->logger = $logger;
        $this->logger->debug("ScraperLyrics\Lyrics::__construct");
        $this->lyrics = null;
        $this->source = null;
        $this->cache = $cache;
    }

    public function __destruct()
    {
        $this->logger->debug("ScraperLyrics\Lyrics::__destruct");
    }

    public function parseTitle(string $title): string
    {
        // ugly hack to scrap "live versions"
        return (mb_trim(preg_replace("/ \(live\)$/i", "", mb_trim($title))));
    }

    public function parseArtist(string $artist): string
    {
        return (mb_trim($artist));
    }

    private function saveCache(string $hash, string $raw): bool
    {
        if ($this->cache !== null) {
            return ($this->cache->save($hash, $raw));
        } else {
            return (false);
        }
    }

    private function getCache(string $hash): bool
    {
        if ($this->cache !== null) {
            if ($cache = $this->cache->get($hash)) {
                $this->lyrics = $cache;
                return (true);
            } else {
                return (false);
            }
        } else {
            return (false);
        }
    }

    public function scrapFromSourceProvider(string $title, string $artist, \aportela\ScraperLyrics\SourceProvider $sourceProvider): bool
    {
        $this->title = $this->parseTitle($title);
        $this->artist = $this->parseArtist($artist);
        $this->source = null;
        if (!empty($this->title)) {
            if (!empty($this->artist)) {
                $cacheHash = md5(mb_strtolower(mb_trim($this->title) . mb_trim($this->artist) . $sourceProvider->value));
                if (! $this->getCache($cacheHash)) {
                    switch ($sourceProvider) {
                        case \aportela\ScraperLyrics\SourceProvider::SEARCH_ENGINE_DUCKDUCKGO:
                            $scraper = new \aportela\ScraperLyrics\SourceProviders\SearchEngineDuckDuckGo($this->logger);
                            try {
                                $this->lyrics = $scraper->scrap($this->title, $this->artist);
                                if (!empty($this->lyrics)) {
                                    $this->source = $sourceProvider->value;
                                    $this->saveCache($cacheHash, $this->lyrics);
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
                                    $this->source = $sourceProvider->value;
                                    $this->saveCache($cacheHash, $this->lyrics);
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
                                    $this->source = $sourceProvider->value;
                                    $this->saveCache($cacheHash, $this->lyrics);
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
                                    $this->source = $sourceProvider->value;
                                    $this->saveCache($cacheHash, $this->lyrics);
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
                                    $this->source = $sourceProvider->value;
                                    $this->saveCache($cacheHash, $this->lyrics);
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
                    $this->source = $sourceProvider->value;
                    return (true);
                }
            } else {
                throw new \InvalidArgumentException("artist");
            }
        } else {
            throw new \InvalidArgumentException("title");
        }
        return (false);
    }

    /**
     * @param array<\aportela\ScraperLyrics\SourceProvider> $providers
     */
    public function scrap(string $title, string $artist, ?array $providers = []): bool
    {
        foreach (
            (isset($providers) && count($providers) > 0) ?
                $providers :
                [
                    \aportela\ScraperLyrics\SourceProvider::SEARCH_ENGINE_DUCKDUCKGO,
                    \aportela\ScraperLyrics\SourceProvider::MUSIXMATCH,
                    \aportela\ScraperLyrics\SourceProvider::LYRICS_MANIA,
                    \aportela\ScraperLyrics\SourceProvider::GENIUS,
                    \aportela\ScraperLyrics\SourceProvider::AZLYRICS,
                ] as $provider
        ) {
            if ($this->scrapFromSourceProvider($title, $artist, $provider)) {
                return (true);
            }
        }
        return (false);
    }
}
