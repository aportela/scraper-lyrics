<?php

declare(strict_types=1);

namespace aportela\ScraperLyrics\SourceProviders;

final class LyricsMania extends BaseProvider
{
    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    private function getLink(string $title, string $artist): string
    {
        $httpResponse = $this->http->GET("https://www.lyricsmania.com/search.php", ["k" => sprintf('"%s" "%s"', $title, $artist)]);
        if ($httpResponse->code === 200) {
            if (!in_array($httpResponse->body, [null, '', '0'], true)) {
                libxml_use_internal_errors(true);
                $domDocument = new \DomDocument();
                if ($domDocument->loadHTML($httpResponse->body)) {
                    $domxPath = new \DOMXPath($domDocument);
                    $expression = '//ul[@class="search"]/li/a';
                    $nodes = $domxPath->query($expression);
                    if ($nodes != false && $nodes->count() > 0 && $nodes[0] instanceof \DOMElement) {
                        return ("https://www.lyricsmania.com" .  $nodes[0]->getAttribute('href'));
                    } else {
                        $this->logger->error(\aportela\ScraperLyrics\SourceProviders\LyricsMania::class . '::getLink - Error: missing html xpath nodes', [$expression]);
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse('Missing html xpath nodes: ' . $expression);
                    }
                } else {
                    $this->logger->error(\aportela\ScraperLyrics\SourceProviders\LyricsMania::class . '::getLink - Error: invalid html body');
                    throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Invalid HTML body");
                }
            } else {
                $this->logger->error(\aportela\ScraperLyrics\SourceProviders\LyricsMania::class . '::getLink - Error: empty body');
                throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Error: empty body");
            }
        } else {
            $this->logger->error(\aportela\ScraperLyrics\SourceProviders\LyricsMania::class . '::getLink - Error: invalid HTTP response code: ' . $httpResponse->code);
            throw new \aportela\ScraperLyrics\Exception\HTTPException('Invalid HTTP response code: ' . $httpResponse->code);
        }
    }

    public function scrap(string $title, string $artist): string
    {
        $httpResponse = $this->http->GET($this->getLink($title, $artist));
        if ($httpResponse->code === 200) {
            if (!in_array($httpResponse->body, [null, '', '0'], true)) {
                libxml_use_internal_errors(true);
                $domDocument = new \DomDocument();
                if ($domDocument->loadHTML(str_ireplace(["<br>", "<br/>", "<br />"], PHP_EOL, $httpResponse->body))) {
                    $domxPath = new \DOMXPath($domDocument);
                    $expression = '//div[@class="lyrics-body"]';
                    $nodes = $domxPath->query($expression);
                    if ($nodes !== false && $nodes->count() > 0) {
                        $data = null;
                        foreach ($nodes as $node) {
                            if ($node instanceof \DOMElement) {
                                $data .= mb_trim($node->textContent) . PHP_EOL;
                            }
                        }

                        if (is_string($data)) {
                            $data = mb_trim($data);
                        }

                        if (!in_array($data, [null, '', '0'], true)) {
                            return ($data);
                        } else {
                            $this->logger->error(\aportela\ScraperLyrics\SourceProviders\LyricsMania::class . '::scrap - Error: empty lyrics');
                            throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Empty lyrics");
                        }
                    } else {
                        $this->logger->error(\aportela\ScraperLyrics\SourceProviders\LyricsMania::class . '::scrap - Error: missing html xpath nodes', [$expression]);
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse('Missing html xpath nodes: ' . $expression);
                    }
                } else {
                    $this->logger->error(\aportela\ScraperLyrics\SourceProviders\LyricsMania::class . '::scrap - Error: invalid html body');
                    throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Invalid HTML body");
                }
            } else {
                $this->logger->error(\aportela\ScraperLyrics\SourceProviders\LyricsMania::class . '::scrap - Error: empty body');
                throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Error: empty body");
            }
        } else {
            $this->logger->error(\aportela\ScraperLyrics\SourceProviders\LyricsMania::class . '::scrap - Error: invalid HTTP response code: ' . $httpResponse->code);
            throw new \aportela\ScraperLyrics\Exception\HTTPException('Invalid HTTP response code: ' . $httpResponse->code);
        }
    }
}
