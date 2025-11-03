<?php

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
        $response = $this->http->GET("https://www.azlyrics.com/geo.js");
        if ($response->code == 200) {
            if (!empty($response->body)) {
                $pattern1 = '/ep\.setAttribute\("name", "(\w+)"\);/';
                if (preg_match_all($pattern1, $response->body, $nameMatches)) {
                    // TODO: check $nameMatches && $valueMatches
                    $pattern2 = '/ep\.setAttribute\("value", "(\w+)"\);/';
                    if (preg_match_all($pattern2, $response->body, $valueMatches)) {
                        return (["name" => $nameMatches[1][0], "value" => $valueMatches[1][0]]);
                    } else {
                        $this->logger->error("\aportela\ScraperLyrics\SourceProviders\AZLyrics::getInputHidden - Error: missing setAttribute name pattern");
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Error: missing setAttribute name pattern");
                    }
                } else {
                    $this->logger->error("\aportela\ScraperLyrics\SourceProviders\AZLyrics::getInputHidden - Error: missing setAttribute value pattern");
                    throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Error: missing setAttribute value pattern");
                }
            } else {
                $this->logger->error("\aportela\ScraperLyrics\SourceProviders\AZLyrics::getInputHidden - Error: empty body");
                throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Error: empty body");
            }
        } else {
            $this->logger->error("\aportela\ScraperLyrics\SourceProviders\AZLyrics::getInputHidden - Error: invalid HTTP response code: {$response->code}");
            throw new \aportela\ScraperLyrics\Exception\HTTPException("Invalid HTTP response code: {$response->code}");
        }
    }


    private function getLink(string $title, string $artist): string
    {
        $input = $this->getInputHidden();
        $response = $this->http->GET("https://www.azlyrics.com/search/", ["q" => sprintf("\"%s\" \"%s\"", $title, $artist), $input["name"] => $input["value"]]);
        if ($response->code == 200) {
            if (!empty($response->body)) {
                $doc = new \DomDocument();
                libxml_use_internal_errors(true);
                if ($doc->loadHTML($response->body)) {
                    $xpath = new \DOMXPath($doc);
                    $expression = '//table/tr/td/a';
                    $nodes = $xpath->query($expression);
                    if ($nodes !== false && $nodes->count() > 0) {
                        return ($nodes[0]->getAttribute("href"));
                    } else {
                        $this->logger->error("\aportela\ScraperLyrics\SourceProviders\AZLyrics::getLink - Error: missing html xpath nodes", [$expression]);
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Missing html xpath nodes: {$expression}");
                    }
                } else {
                    $this->logger->error("\aportela\ScraperLyrics\SourceProviders\AZLyrics::getLink - Error: invalid html body");
                    throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Invalid HTML body");
                }
            } else {
                $this->logger->error("\aportela\ScraperLyrics\SourceProviders\AZLyrics::getLink - Error: empty body");
                throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Error: empty body");
            }
        } else {
            $this->logger->error("\aportela\ScraperLyrics\SourceProviders\AZLyrics::getLink - Error: invalid HTTP response code: {$response->code}");
            throw new \aportela\ScraperLyrics\Exception\HTTPException("Invalid HTTP response code: {$response->code}");
        }
    }

    public function scrap(string $title, string $artist): string
    {
        $link = $this->getLink($title, $artist);
        $this->http->setReferer("https://search.azlyrics.com/search.php");
        $response = $this->http->GET($link);
        if ($response->code == 200) {
            if (!empty($response->body)) {
                $doc = new \DomDocument();
                if ($doc->loadHTML($this->parseHTMLCRLF($response->body))) {
                    $xpath = new \DOMXPath($doc);
                    $expression = '//div[@class="col-xs-12 col-lg-8 text-center"]/div';
                    $nodes = $xpath->query($expression);
                    if ($nodes !== false && $nodes->count() == 6) {
                        $data = mb_trim($nodes[4]->textContent);
                        if (!empty($data)) {
                            return ($data);
                        } else {
                            $this->logger->error("\aportela\ScraperLyrics\SourceProviders\AZLyrics::scrap - Error: empty lyrics");
                            throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Empty lyrics");
                        }
                    } else {
                        $this->logger->error("\aportela\ScraperLyrics\SourceProviders\AZLyrics::scrap - Error: missing html xpath nodes", [$expression]);
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Missing html xpath nodes: {$expression}");
                    }
                } else {
                    $this->logger->error("\aportela\ScraperLyrics\SourceProviders\AZLyrics::scrap - Error: invalid html body");
                    throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Invalid HTML body");
                }
            } else {
                $this->logger->error("\aportela\ScraperLyrics\SourceProviders\AZLyrics::scrap - Error: empty body");
                throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Error: empty body");
            }
        } else {
            $this->logger->error("\aportela\ScraperLyrics\SourceProviders\AZLyrics::scrap - Error: invalid HTTP response code: {$response->code}");
            throw new \aportela\ScraperLyrics\Exception\HTTPException("Invalid HTTP response code: {$response->code}");
        }
    }
}
