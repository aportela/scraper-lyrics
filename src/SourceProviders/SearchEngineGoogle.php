<?php

namespace aportela\ScraperLyrics\SourceProviders;

final class SearchEngineGoogle extends BaseProvider
{
    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        // fake user agent as a real browser
        parent::__construct($logger, \aportela\HTTPRequestWrapper\UserAgent::CHROME_WINDOWS_10->value);
    }

    public function scrap(string $title, string $artist): string
    {
        // get cookies & set referer so that Google does not realize so easily that we are using a script and not a browser
        // I can't guarantee it will always work, but the request will be less suspicious in a quick analysis
        $response = $this->http->HEAD("https://www.google.com/");
        $this->http->setReferer("https://www.google.com/");
        $response = $this->http->GET("https://www.google.com/search", ["q" => sprintf("lyrics \"%s\" \"%s\"", $title, $artist)]);
        if ($response->code == 200) {
            if (!empty($response->body)) {
                libxml_use_internal_errors(true);
                $doc = new \DomDocument();
                if ($doc->loadHTML($response->body)) {
                    $xpath = new \DOMXPath($doc);
                    $nodes = $xpath->query('//div[@jsname="WbKHeb"]//span');
                    if ($nodes != false) {
                        if ($nodes->count() > 0) {
                            $data = null;
                            foreach ($nodes as $key => $node) {
                                $data .= trim($node->textContent) . PHP_EOL;
                            }
                            if (!empty($data)) {
                                return ($data);
                            } else {
                                throw new \aportela\ScraperLyrics\Exception\NotFoundException("");
                            }
                        } else {
                            throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse(sprintf("HTML Nodes %s not found", '//div[@jsname="WbKHeb"]//span'));
                        }
                    } else {
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse(sprintf("HTML Nodes %s not found", '//div[@jsname="WbKHeb"]//span'));
                    }
                } else {
                    throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Invalid HTML body");
                }
            } else {
                throw new \aportela\ScraperLyrics\Exception\HTTPException("Invalid HTTP (empty) body");
            }
        } else {
            throw new \aportela\ScraperLyrics\Exception\HTTPException("Invalid HTTP response code: " . $response->code);
        }
    }
}
