<?php

declare(strict_types=1);

namespace aportela\ScraperLyrics\SourceProviders;

final class AZLyrics extends BaseProvider
{
    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        // fake user agent as a real browser
        parent::__construct($logger, \aportela\HTTPRequestWrapper\UserAgent::EDGE_WINDOWS_10->value);
    }

    /**
     * @return array<string,string>
     */
    private function getInputHidden(): array
    {
        $this->http->setReferer("https://www.azlyrics.com/");
        $httpResponse = $this->http->GET("https://www.azlyrics.com/geo.js");
        if ($httpResponse->code === 200) {
            if (!in_array($httpResponse->body, [null, '', '0'], true)) {
                $pattern1 = '/ep\.setAttribute\("name", "(\w+)"\);/';
                if (preg_match_all($pattern1, $httpResponse->body, $nameMatches)) {
                    // TODO: check $nameMatches && $valueMatches
                    $pattern2 = '/ep\.setAttribute\("value", "(\w+)"\);/';
                    if (preg_match_all($pattern2, $httpResponse->body, $valueMatches)) {
                        return (["name" => $nameMatches[1][0], "value" => $valueMatches[1][0]]);
                    } else {
                        $this->logger->error(\aportela\ScraperLyrics\SourceProviders\AZLyrics::class . '::getInputHidden - Error: missing setAttribute name pattern');
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Error: missing setAttribute name pattern");
                    }
                } else {
                    $this->logger->error(\aportela\ScraperLyrics\SourceProviders\AZLyrics::class . '::getInputHidden - Error: missing setAttribute value pattern');
                    throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Error: missing setAttribute value pattern");
                }
            } else {
                $this->logger->error(\aportela\ScraperLyrics\SourceProviders\AZLyrics::class . '::getInputHidden - Error: empty body');
                throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Error: empty body");
            }
        } else {
            $this->logger->error(\aportela\ScraperLyrics\SourceProviders\AZLyrics::class . '::getInputHidden - Error: invalid HTTP response code: ' . $httpResponse->code);
            throw new \aportela\ScraperLyrics\Exception\HTTPException('Invalid HTTP response code: ' . $httpResponse->code);
        }
    }


    private function getLink(string $title, string $artist): string
    {
        $input = $this->getInputHidden();
        $httpResponse = $this->http->GET("https://www.azlyrics.com/search/", ["q" => sprintf('"%s" "%s"', $title, $artist), $input["name"] => $input["value"]]);
        if ($httpResponse->code === 200) {
            if (!in_array($httpResponse->body, [null, '', '0'], true)) {
                $domDocument = new \DomDocument();
                libxml_use_internal_errors(true);
                if ($domDocument->loadHTML($httpResponse->body)) {
                    $domxPath = new \DOMXPath($domDocument);
                    $expression = '//table/tr/td/a';
                    $nodes = $domxPath->query($expression);
                    if ($nodes !== false && $nodes->count() > 0 && $nodes[0] instanceof \DOMElement) {
                        return ($nodes[0]->getAttribute("href"));
                    } else {
                        $this->logger->error(\aportela\ScraperLyrics\SourceProviders\AZLyrics::class . '::getLink - Error: missing html xpath nodes', [$expression]);
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse('Missing html xpath nodes: ' . $expression);
                    }
                } else {
                    $this->logger->error(\aportela\ScraperLyrics\SourceProviders\AZLyrics::class . '::getLink - Error: invalid html body');
                    throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Invalid HTML body");
                }
            } else {
                $this->logger->error(\aportela\ScraperLyrics\SourceProviders\AZLyrics::class . '::getLink - Error: empty body');
                throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Error: empty body");
            }
        } else {
            $this->logger->error(\aportela\ScraperLyrics\SourceProviders\AZLyrics::class . '::getLink - Error: invalid HTTP response code: ' . $httpResponse->code);
            throw new \aportela\ScraperLyrics\Exception\HTTPException('Invalid HTTP response code: ' . $httpResponse->code);
        }
    }

    public function scrap(string $title, string $artist): string
    {
        $link = $this->getLink($title, $artist);
        $this->http->setReferer("https://search.azlyrics.com/search.php");
        $httpResponse = $this->http->GET($link);
        if ($httpResponse->code === 200) {
            if (!in_array($httpResponse->body, [null, '', '0'], true)) {
                $domDocument = new \DomDocument();
                if ($domDocument->loadHTML($this->parseHTMLCRLF($httpResponse->body))) {
                    $domxPath = new \DOMXPath($domDocument);
                    $expression = '//div[@class="col-xs-12 col-lg-8 text-center"]/div';
                    $nodes = $domxPath->query($expression);
                    if ($nodes !== false && $nodes->count() === 6 && is_object($nodes[4]) && isset($nodes[4]->textContent) && is_string($nodes[4]->textContent)) {
                        $data = mb_trim($nodes[4]->textContent);
                        if ($data !== '' && $data !== '0') {
                            return ($data);
                        } else {
                            $this->logger->error(\aportela\ScraperLyrics\SourceProviders\AZLyrics::class . '::scrap - Error: empty lyrics');
                            throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Empty lyrics");
                        }
                    } else {
                        $this->logger->error(\aportela\ScraperLyrics\SourceProviders\AZLyrics::class . '::scrap - Error: missing html xpath nodes', [$expression]);
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse('Missing html xpath nodes: ' . $expression);
                    }
                } else {
                    $this->logger->error(\aportela\ScraperLyrics\SourceProviders\AZLyrics::class . '::scrap - Error: invalid html body');
                    throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Invalid HTML body");
                }
            } else {
                $this->logger->error(\aportela\ScraperLyrics\SourceProviders\AZLyrics::class . '::scrap - Error: empty body');
                throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Error: empty body");
            }
        } else {
            $this->logger->error(\aportela\ScraperLyrics\SourceProviders\AZLyrics::class . '::scrap - Error: invalid HTTP response code: ' . $httpResponse->code);
            throw new \aportela\ScraperLyrics\Exception\HTTPException('Invalid HTTP response code: ' . $httpResponse->code);
        }
    }
}
