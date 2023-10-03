<?php

namespace aportela\ScraperLyrics\SourceProviders;

final class SearchEngineBing extends BaseProvider
{
    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        // fake user agent as a real browser
        parent::__construct($logger, \aportela\HTTPRequestWrapper\UserAgent::EDGE_WINDOWS_10->value);
    }

    public function scrap(string $title, string $artist): string
    {
        // get cookies & set referer so that Bing does not realize so easily that we are using a script and not a browser
        // I can't guarantee it will always work, but the request will be less suspicious in a quick analysis
        $response = $this->http->HEAD("https://www.bing.com/");
        $this->http->setReferer("https://ntp.msn.com/");
        $response = $this->http->GET("https://www.bing.com/search", ["q" => sprintf("lyrics \"%s\" \"%s\"", $title, $artist)]);
        if ($response->code == 200) {
            if (!empty($response->body)) {
                libxml_use_internal_errors(true);
                $doc = new \DomDocument();
                if ($doc->loadHTML(str_ireplace(array("<br>", "<br/>", "<br />"), PHP_EOL, $response->body))) {
                    $xpath = new \DOMXPath($doc);
                    $nodes = $xpath->query('//div[@class="lyrics"]//div');
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
                            throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse(sprintf("HTML Nodes %s not found", '//div[@class="lyrics"]//div'));
                        }
                    } else {
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse(sprintf("HTML Nodes %s not found", '//div[@class="lyrics"]//div'));
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
