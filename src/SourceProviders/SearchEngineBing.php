<?php

namespace aportela\ScraperLyrics\SourceProviders;

final class SearchEngineBing extends BaseProvider
{
    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        parent::__construct($logger, null);
    }

    public function scrap(string $title, string $artist): string
    {
        $response = $this->http->GET(sprintf("https://www.bing.com/search?%s", http_build_query(["q" => sprintf("lyrics \"%s\" \"%s\"", $title, $artist)])));
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
