<?php

namespace aportela\ScraperLyrics\SourceProviders;

final class Musicmatch extends BaseProvider
{
    private const USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36 Edg/116.0.1938.81";

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        parent::__construct($logger, self::USER_AGENT);
    }

    public function scrap(string $title, string $artist): string
    {
        // get cookies & set referer so that Musicmatch does not realize so easily that we are using a script and not a browser
        // I can't guarantee it will always work, but the request will be less suspicious in a quick analysis
        $response = $this->http->HEAD("https://www.musixmatch.com/");
        // "simulate" that we came to this from google search
        $this->http->setReferer("https://www.google.com/search?client=firefox-b-d&q=" . urlencode("musicmatch lyrics " . $title . " " . $artist));
        //$response = $this->http->GET(sprintf("https://www.musixmatch.com%s/embed", $link));
        $response = $this->http->GET(sprintf("https://www.musixmatch.com/lyrics/%s/%s", str_replace(" ", "-", $artist), str_replace(" ", "-", $title)));
        if ($response->code == 200) {
            if (!empty($response->body)) {
                libxml_use_internal_errors(true);
                $doc = new \DomDocument();
                if ($doc->loadHTML(str_ireplace(array("<br>", "<br/>", "<br />"), PHP_EOL, $response->body))) {
                    $xpath = new \DOMXPath($doc);
                    // lyric paragraphs are contained on multiple p childs of <div class="track-widget-body">
                    $nodes = $xpath->query('//div[contains(@class, "r-11rrj2j") and contains(@class, "r-15zivkp") and @dir="auto"]');
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
                            throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse(sprintf("HTML Nodes %s not found", '//div[@class="css-146c3p1 r-1inkyih r-11rrj2j r-13awgt0 r-fdjqy7 r-1dxmaum r-1it3c9n r-135wba7"]'));
                        }
                    } else {
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse(sprintf("HTML Nodes %s not found", '//div[@class="css-146c3p1 r-1inkyih r-11rrj2j r-13awgt0 r-fdjqy7 r-1dxmaum r-1it3c9n r-135wba7"]'));
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
