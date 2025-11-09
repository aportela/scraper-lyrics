<?php

declare(strict_types=1);

namespace aportela\ScraperLyrics\SourceProviders;

final class Musicmatch extends BaseProvider
{
    private const string USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36 Edg/116.0.1938.81";

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
        if ($response->code === 200) {
            if (!in_array($response->body, [null, '', '0'], true)) {
                libxml_use_internal_errors(true);
                $domDocument = new \DomDocument();
                if ($domDocument->loadHTML(str_ireplace(["<br>", "<br/>", "<br />"], PHP_EOL, $response->body))) {
                    $domxPath = new \DOMXPath($domDocument);
                    // lyric paragraphs are contained on multiple p childs of <div class="track-widget-body">
                    $expression = '//div[contains(@class, "r-11rrj2j") and contains(@class, "r-15zivkp") and @dir="auto"]';
                    $nodes = $domxPath->query($expression);
                    if ($nodes !== false && $nodes->count() > 0) {
                        $data = null;
                        foreach ($nodes as $node) {
                            if (isset($node->textContent) && is_string($node->textContent)) {
                                $data .= mb_trim($node->textContent) . PHP_EOL;
                            }
                        }

                        if (is_string($data)) {
                            $data = mb_trim($data);
                        }

                        if (!in_array($data, [null, '', '0'], true)) {
                            return ($data);
                        } else {
                            $this->logger->error(\aportela\ScraperLyrics\SourceProviders\Musicmatch::class . '::scrap - Error: empty lyrics');
                            throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Empty lyrics");
                        }
                    } else {
                        $this->logger->error(\aportela\ScraperLyrics\SourceProviders\Musicmatch::class . '::scrap - Error: missing html xpath nodes', [$expression]);
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse('Missing html xpath nodes: ' . $expression);
                    }
                } else {
                    $this->logger->error(\aportela\ScraperLyrics\SourceProviders\Musicmatch::class . '::scrap - Error: invalid html body');
                    throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Invalid HTML body");
                }
            } else {
                $this->logger->error(\aportela\ScraperLyrics\SourceProviders\Musicmatch::class . '::scrap - Error: empty body');
                throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Error: empty body");
            }
        } else {
            $this->logger->error(\aportela\ScraperLyrics\SourceProviders\Musicmatch::class . '::scrap - Error: invalid HTTP response code: ' . $response->code);
            throw new \aportela\ScraperLyrics\Exception\HTTPException('Invalid HTTP response code: ' . $response->code);
        }
    }
}
