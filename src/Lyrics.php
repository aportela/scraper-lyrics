<?php

declare(strict_types=1);

namespace aportela\ScraperLyrics;

class Lyrics
{
    protected \aportela\HTTPRequestWrapper\HTTPRequest $http;

    // (AT THIS TIME) this is REQUIRED/IMPORTANT, with another user agents (on GOOGLE) the search response is not the same (do not include lyrics!)
    public const USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36 Edg/116.0.1938.81";

    private string $title;

    private string $artist;

    private ?string $lyrics = null;

    private ?string $source = null;

    public function __construct(protected \Psr\Log\LoggerInterface $logger, protected ?\aportela\SimpleFSCache\Cache $cache = null)
    {
    }

    public function getTitle(): ?string
    {
        return ($this->title);
    }

    public function getArtist(): ?string
    {
        return ($this->artist);
    }

    public function getLyrics(): ?string
    {
        return ($this->lyrics);
    }

    public function getSource(): ?string
    {
        return ($this->source);
    }

    public function parseTitle(string $title): string
    {
        // ugly hack to scrap "live versions"
        // example: "We are the champions (live)" is converted to "We are the champions"
        $replacedTitle = preg_replace("/ \(live\)$/i", "", mb_trim($title));
        if ($replacedTitle !== null) {
            return (mb_trim($replacedTitle));
        } else {
            return ("");
        }
    }

    public function parseArtist(string $artist): string
    {
        return (mb_trim($artist));
    }

    private function saveCache(string $hash, string $raw): bool
    {
        if ($this->cache instanceof \aportela\SimpleFSCache\Cache) {
            return ($this->cache->set($hash, $raw));
        } else {
            return (false);
        }
    }

    private function getCache(string $hash): bool
    {
        $this->lyrics = null;
        if ($this->cache instanceof \aportela\SimpleFSCache\Cache) {
            $cacheData = $this->cache->get($hash, false);
            if (is_string($cacheData)) {
                $this->lyrics = $cacheData;
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
        if ($this->title !== '' && $this->title !== '0') {
            if ($this->artist !== '' && $this->artist !== '0') {
                $cacheHash = md5(mb_strtolower(mb_trim($this->title) . mb_trim($this->artist) . $sourceProvider->value));
                if (! $this->getCache($cacheHash)) {
                    $scraped = false;
                    switch ($sourceProvider) {
                        case \aportela\ScraperLyrics\SourceProvider::SEARCH_ENGINE_DUCKDUCKGO:
                            $scraper = new \aportela\ScraperLyrics\SourceProviders\SearchEngineDuckDuckGo($this->logger);
                            try {
                                $this->lyrics = $scraper->scrap($this->title, $this->artist);
                                if ($this->lyrics !== '' && $this->lyrics !== '0') {
                                    $this->source = $sourceProvider->value;
                                    $this->saveCache($cacheHash, $this->lyrics);
                                    $scraped = true;
                                }
                            } catch (\Throwable $e) {
                                $this->logger->debug("ScraperLyrics\Lyrics::search - Error scraping on duckduckgo search engine: " . $e->getMessage());
                            }

                            break;
                        case \aportela\ScraperLyrics\SourceProvider::LYRICS_MANIA:
                            $scraper = new \aportela\ScraperLyrics\SourceProviders\LyricsMania($this->logger);
                            try {
                                $this->lyrics = $scraper->scrap($this->title, $this->artist);
                                if ($this->lyrics !== '' && $this->lyrics !== '0') {
                                    $this->source = $sourceProvider->value;
                                    $this->saveCache($cacheHash, $this->lyrics);
                                    $scraped = true;
                                }
                            } catch (\Throwable $e) {
                                $this->logger->debug("ScraperLyrics\Lyrics::search - Error scraping on lyricsmania: " . $e->getMessage());
                            }

                            break;
                        case \aportela\ScraperLyrics\SourceProvider::GENIUS:
                            $scraper = new \aportela\ScraperLyrics\SourceProviders\Genius($this->logger);
                            try {
                                $this->lyrics = $scraper->scrap($this->title, $this->artist);
                                if ($this->lyrics !== '' && $this->lyrics !== '0') {
                                    $this->source = $sourceProvider->value;
                                    $this->saveCache($cacheHash, $this->lyrics);
                                    $scraped = true;
                                }
                            } catch (\Throwable $e) {
                                $this->logger->debug("ScraperLyrics\Lyrics::search - Error scraping on genius: " . $e->getMessage());
                            }

                            break;
                        case \aportela\ScraperLyrics\SourceProvider::MUSIXMATCH:
                            $scraper = new \aportela\ScraperLyrics\SourceProviders\Musicmatch($this->logger);
                            try {
                                $this->lyrics = $scraper->scrap($this->title, $this->artist);
                                if ($this->lyrics !== '' && $this->lyrics !== '0') {
                                    $this->source = $sourceProvider->value;
                                    $this->saveCache($cacheHash, $this->lyrics);
                                    $scraped = true;
                                }
                            } catch (\Throwable $e) {
                                $this->logger->debug("ScraperLyrics\Lyrics::search - Error scraping on musicmatch: " . $e->getMessage());
                            }

                            break;
                        case \aportela\ScraperLyrics\SourceProvider::AZLYRICS:
                            $scraper = new \aportela\ScraperLyrics\SourceProviders\AZLyrics($this->logger);
                            try {
                                $this->lyrics = $scraper->scrap($this->title, $this->artist);
                                if ($this->lyrics !== '' && $this->lyrics !== '0') {
                                    $this->source = $sourceProvider->value;
                                    $this->saveCache($cacheHash, $this->lyrics);
                                    $scraped = true;
                                }
                            } catch (\Throwable $e) {
                                $this->logger->debug("ScraperLyrics\Lyrics::search - Error scraping on azlyrics: " . $e->getMessage());
                            }

                            break;
                        default:
                            return (false);
                    }

                    return ($scraped);
                } else {
                    $this->source = $sourceProvider->value;
                    return (true);
                }
            } else {
                $this->logger->error(\aportela\ScraperLyrics\Lyrics::class . '::scrapFromSourceProvider - Error: invalid title: ' . $title);
                throw new \InvalidArgumentException("artist");
            }
        } else {
            $this->logger->error(\aportela\ScraperLyrics\Lyrics::class . '::scrapFromSourceProvider - Error: invalid artist: ' . $artist);
            throw new \InvalidArgumentException("title");
        }
    }

    /**
     * @param array<\aportela\ScraperLyrics\SourceProvider> $providers
     */
    public function scrap(string $title, string $artist, ?array $providers = []): bool
    {
        return array_any((isset($providers) && $providers !== []) ?
            $providers :
            [
                \aportela\ScraperLyrics\SourceProvider::SEARCH_ENGINE_DUCKDUCKGO,
                \aportela\ScraperLyrics\SourceProvider::MUSIXMATCH,
                \aportela\ScraperLyrics\SourceProvider::LYRICS_MANIA,
                \aportela\ScraperLyrics\SourceProvider::GENIUS,
                \aportela\ScraperLyrics\SourceProvider::AZLYRICS,
            ], fn (\aportela\ScraperLyrics\SourceProvider $sourceProvider): bool => $this->scrapFromSourceProvider($title, $artist, $sourceProvider));
    }
}
