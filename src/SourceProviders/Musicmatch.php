<?php

namespace aportela\ScraperLyrics\SourceProviders;

final class Musicmatch extends BaseProvider
{
    private const USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36 Edg/116.0.1938.81";
    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        parent::__construct($logger, self::USER_AGENT);
    }

    private function getLink(string $title, string $artist): string
    {
        $this->http->setReferer("https://www.musixmatch.com/");
        echo sprintf("https://www.musixmatch.com/search/%s/tracks", urlencode($title . " " . $artist)) . PHP_EOL;
        $response = $this->http->GET(sprintf("https://www.musixmatch.com/search/%s/tracks", urlencode($title . " " . $artist)));
        if ($response->code == 200) {
            if (!empty($response->body)) {
                libxml_use_internal_errors(true);
                $doc = new \DomDocument();
                if ($doc->loadHTML($response->body)) {
                    $xpath = new \DOMXPath($doc);
                    // lyric paragraphs are contained on multiple childs of <div data-lyrics-container>
                    $nodes = $xpath->query('//ul[@class="tracks list thumb-list"]/li/div[@class="track-card media-card has-picture"]/div[@class="media-card-body"]/div[@class="media-card-text"]/h2/a');
                    if ($nodes != false) {
                        if ($nodes->count() > 0) {
                            return ($nodes[0]->getAttribute('href'));
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

    public function scrap(string $title, string $artist): string
    {
        // get cookies & set referer so that Google does not realize so easily that we are using a script and not a browser
        // I can't guarantee it will always work, but the request will be less suspicious in a quick analysis
        $response = $this->http->HEAD("https://www.musixmatch.com/");
        $link = $this->getLink($title, $artist);
        echo $link . PHP_EOL;
        $this->http->setReferer(sprintf("https://www.musixmatch.com/search/%s/tracks", urlencode($title . " " . $artist)));
        $response = $this->http->GET(sprintf("https://www.musixmatch.com%s/embed", $link));
        if ($response->code == 200) {
            if (!empty($response->body)) {
                libxml_use_internal_errors(true);
                $doc = new \DomDocument();
                if ($doc->loadHTML(str_ireplace(array("<br>", "<br/>", "<br />"), PHP_EOL, $response->body))) {
                    $xpath = new \DOMXPath($doc);
                    // lyric paragraphs are contained on multiple p childs of <div class="track-widget-body">
                    $nodes = $xpath->query('//div[@class="track-widget-body"]/p');
                    if ($nodes != false) {
                        if ($nodes->count() > 0) {
                            $data = null;
                            foreach ($nodes as $key => $node) {
                                $data .= trim($node->textContent) . PHP_EOL;
                            }
                            $data = trim($data);
                            if (!empty($data)) {
                                return ($data);
                            } else {
                                throw new \aportela\ScraperLyrics\Exception\NotFoundException("");
                            }
                        } else {
                            throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse(sprintf("HTML Nodes %s not found", '//div[@class="track-widget-body"]/p'));
                        }
                    } else {
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse(sprintf("HTML Nodes %s not found", '//div[@class="track-widget-body"]/p'));
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
