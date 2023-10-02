<?php

namespace aportela\ScraperLyrics\SourceProviders;

final class LyricsMania extends BaseProvider
{
    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        parent::__construct($logger, "");
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
                    // lyric links a <ul class="search"> with <li> <a> childs
                    $nodes = $xpath->query('//ul[@class="search"]/li/a');
                    if ($nodes != false) {
                        if ($nodes->count() > 0) {
                            return ("https://www.lyricsmania.com" . $nodes[0]->getAttribute('href'));
                        }
                    } else {
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse(sprintf("HTML Nodes %s not found", 'ul[@class="search"]/li/a'));
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

    public function scrap(string $title, string $artist): string
    {
        $response = $this->http->GET($this->getLink($title, $artist));
        if ($response->code == 200) {
            if (!empty($response->body)) {
                libxml_use_internal_errors(true);
                $doc = new \DomDocument();
                if ($doc->loadHTML(str_ireplace(array("<br>", "<br/>", "<br />"), PHP_EOL, $response->body))) {
                    $xpath = new \DOMXPath($doc);
                    // lyric paragraphs are contained in a <div class="lyrics-body">
                    $nodes = $xpath->query('//div[@class="lyrics-body"]');
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
                            throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse(sprintf("HTML Nodes %s not found", '//div[@class="lyrics-body"]'));
                        }
                    } else {
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse(sprintf("HTML Nodes %s not found", '//div[@class="lyrics-body"]'));
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
