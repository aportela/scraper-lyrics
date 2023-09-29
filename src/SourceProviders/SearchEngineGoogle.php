<?php

namespace aportela\ScraperLyrics\SourceProviders;

final class SearchEngineGoogle extends BaseProvider
{
    // WARNING: (AT THIS TIME) this is REQUIRED/IMPORTANT, with another user agents (on GOOGLE) the search response is not the same (do not include lyrics!)
    private const USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36 Edg/116.0.1938.81";

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        parent::__construct($logger, self::USER_AGENT);
    }

    public function scrap(string $title, string $artist): string
    {
        $response = $this->http->GET(sprintf("https://www.google.com/search?client=firefox-b-d&%s", http_build_query(["q" => sprintf("lyrics \"%s\" \"%s\"", $title, $artist)])));
        if ($response->code == 200 && !empty($response->body)) {
            libxml_use_internal_errors(true);
            $doc = new \DomDocument();
            if ($doc->loadHTML($response->body)) {
                $xpath = new \DOMXPath($doc);
                // lyric paragraphs are contained on a <div jsname="WbKHeb"> with <span> childs
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
                throw new \aportela\ScraperLyrics\Exception\HTTPException("Invalid HTTP (empty) body");
            }
        } else {
            throw new \aportela\ScraperLyrics\Exception\HTTPException("Invalid HTTP response code: " . $response->code);
        }
    }
}
