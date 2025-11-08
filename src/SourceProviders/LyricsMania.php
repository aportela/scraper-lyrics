<?php

namespace aportela\ScraperLyrics\SourceProviders;

final class LyricsMania extends BaseProvider
{
    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        parent::__construct($logger, null);
    }

    private function getLink(string $title, string $artist): string
    {
        $response = $this->http->GET("https://www.lyricsmania.com/search.php", ["k" => sprintf("\"%s\" \"%s\"", $title, $artist)]);
        if ($response->code == 200) {
            if (!empty($response->body)) {
                libxml_use_internal_errors(true);
                $doc = new \DomDocument();
                if ($doc->loadHTML($response->body)) {
                    $xpath = new \DOMXPath($doc);
                    $expression = '//ul[@class="search"]/li/a';
                    $nodes = $xpath->query($expression);
                    if ($nodes != false && $nodes->count() > 0 && $nodes[0] instanceof \DOMElement) {
                        return ("https://www.lyricsmania.com" .  $nodes[0]->getAttribute('href'));
                    } else {
                        $this->logger->error("\aportela\ScraperLyrics\SourceProviders\LyricsMania::getLink - Error: missing html xpath nodes", [$expression]);
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Missing html xpath nodes: {$expression}");
                    }
                } else {
                    $this->logger->error("\aportela\ScraperLyrics\SourceProviders\LyricsMania::getLink - Error: invalid html body");
                    throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Invalid HTML body");
                }
            } else {
                $this->logger->error("\aportela\ScraperLyrics\SourceProviders\LyricsMania::getLink - Error: empty body");
                throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Error: empty body");
            }
        } else {
            $this->logger->error("\aportela\ScraperLyrics\SourceProviders\LyricsMania::getLink - Error: invalid HTTP response code: {$response->code}");
            throw new \aportela\ScraperLyrics\Exception\HTTPException("Invalid HTTP response code: {$response->code}");
        }
    }

    public function scrap(string $title, string $artist): string
    {
        $response = $this->http->GET($this->getLink($title, $artist));
        if ($response->code == 200) {
            if (!empty($response->body)) {
                libxml_use_internal_errors(true);
                $doc = new \DomDocument();
                if ($doc->loadHTML(str_ireplace(array("<br>", "<br/>", "<br />"), PHP_EOL, $response->body))) {
                    $xpath = new \DOMXPath($doc);
                    $expression = '//div[@class="lyrics-body"]';
                    $nodes = $xpath->query($expression);
                    if ($nodes !== false && $nodes->count() > 0) {
                        $data = null;
                        foreach ($nodes as $key => $node) {
                            if ($node instanceof \DOMElement) {
                                $data .= mb_trim($node->textContent) . PHP_EOL;
                            }
                        }
                        if (is_string($data)) {
                            $data = mb_trim($data);
                        }
                        if (!empty($data)) {
                            return ($data);
                        } else {
                            $this->logger->error("\aportela\ScraperLyrics\SourceProviders\LyricsMania::scrap - Error: empty lyrics");
                            throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Empty lyrics");
                        }
                    } else {
                        $this->logger->error("\aportela\ScraperLyrics\SourceProviders\LyricsMania::scrap - Error: missing html xpath nodes", [$expression]);
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Missing html xpath nodes: {$expression}");
                    }
                } else {
                    $this->logger->error("\aportela\ScraperLyrics\SourceProviders\LyricsMania::scrap - Error: invalid html body");
                    throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Invalid HTML body");
                }
            } else {
                $this->logger->error("\aportela\ScraperLyrics\SourceProviders\LyricsMania::scrap - Error: empty body");
                throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Error: empty body");
            }
        } else {
            $this->logger->error("\aportela\ScraperLyrics\SourceProviders\LyricsMania::scrap - Error: invalid HTTP response code: {$response->code}");
            throw new \aportela\ScraperLyrics\Exception\HTTPException("Invalid HTTP response code: {$response->code}");
        }
    }
}
