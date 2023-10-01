<?php

namespace aportela\ScraperLyrics\SourceProviders;

final class SearchEngineBing extends BaseProvider
{
    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        parent::__construct($logger, "");
    }

    public function scrap(string $title, string $artist): string
    {
        // I suspect that sometimes BING fail scrap on first call (next are working)
        // At this time, I don't know if it is due to some protection method of the server itself or if it is waiting for some type of previous redirection / cookie set
        // I tried getting cookies with HEAD (Previous to GET), sending Referer header, faking User Agent to Edge, nothing works 100%
        $response = $this->http->GET("https://www.bing.com/search", ["q" => sprintf("lyrics \"%s\" \"%s\"", $title, $artist)]);
        if ($response->code == 200) {
            if (!empty($response->body)) {
                libxml_use_internal_errors(true);
                $doc = new \DomDocument();
                if ($doc->loadHTML(str_ireplace(array("<br>", "<br/>", "<br />"), PHP_EOL, $response->body))) {
                    $xpath = new \DOMXPath($doc);
                    // lyric paragraphs are contained on a <div class="lyrics"> with <span> childs
                    $nodes = $xpath->query('//div[@class="lyrics"]//div');
                    if ($nodes != false) {
                        if ($nodes->count() > 0) {
                            $data = null;
                            foreach ($nodes as $key => $node) {
                                $data .= trim($node->textContent) . PHP_EOL;
                            }
                            if (!empty($data)) {
                                if ($artist == "#") {
                                    die($data);
                                }
                                return ($data);
                            } else {
                                throw new \aportela\ScraperLyrics\Exception\NotFoundException("");
                            }
                        } else {
                            throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse(sprintf("HTML Nodes %s not found", '//div[@jsname="lyrics"]//div'));
                        }
                    } else {
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse(sprintf("HTML Nodes %s not found", '//div[@jsname="lyrics"]//div'));
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
