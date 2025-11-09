<?php

namespace aportela\ScraperLyrics\SourceProviders;

final class Genius extends BaseProvider
{
    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        // fake user agent as a real browser
        parent::__construct($logger, \aportela\HTTPRequestWrapper\UserAgent::EDGE_WINDOWS_10->value);
    }

    private function getLink(string $title, string $artist): string
    {
        // required for _csrf_token COOKIE, we want recognized as a human on next requests
        $this->http->HEAD("https://genius.com/search/embed");
        $this->http->setReferer("https://genius.com/search/embed");
        $response = $this->http->GET("https://genius.com/api/search/multi", ["per_page" => 1, "q" => sprintf("\"%s\" \"%s\"", $title, $artist)]);
        if ($response->code == 200) {
            if (!empty($response->body)) {
                if ($response->is(\aportela\HTTPRequestWrapper\ContentType::JSON)) {
                    $json = json_decode($response->body);
                    if ($json !== null && json_last_error() === JSON_ERROR_NONE) {
                        if (is_object($json) && isset($json->meta) && is_object($json->meta) && isset($json->meta->status) && $json->meta->status == 200) {
                            if (isset($json->response) && is_object($json->response) && isset($json->response->sections) && is_array($json->response->sections) && count($json->response->sections) > 0) {
                                if (is_object($json->response->sections[0]) && isset($json->response->sections[0]->hits) && is_array($json->response->sections[0]->hits)) {
                                    foreach ($json->response->sections[0]->hits as $hit) {
                                        if (is_object($hit) && isset($hit->type) && $hit->type == "song" && isset($hit->result) && is_object($hit->result) && isset($hit->result->url) && is_string($hit->result->url) && filter_var($hit->result->url, FILTER_VALIDATE_URL)) {
                                            return ($hit->result->url);
                                        }
                                    }
                                    $this->logger->error("\aportela\ScraperLyrics\SourceProviders\AZLyrics::getLink - Error: missing property (response->sections[]->hits[]->result->url)");
                                    throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Error: missing property (response->sections[]->hits->result->url))");
                                } else {
                                    $this->logger->error("\aportela\ScraperLyrics\SourceProviders\AZLyrics::getLink - Error: missing property (response->sections[]->hits[])");
                                    throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Error: missing property (response->sections[]->hits[])");
                                }
                            } else {
                                $this->logger->error("\aportela\ScraperLyrics\SourceProviders\AZLyrics::getLink - Error: missing property (response->sections[])");
                                throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Error: missing property (response->sections[])");
                            }
                        } else {
                            $this->logger->error("\aportela\ScraperLyrics\SourceProviders\AZLyrics::getLink - Error: missing property (meta->status == 200)");
                            throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Error: missing property (meta->status == 200)");
                        }
                    } else {
                        $this->logger->error("\aportela\ScraperLyrics\SourceProviders\Genius::getLink - Error: invalid JSON", [json_last_error(), json_last_error_msg()]);
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Invalid JSON: " . json_last_error_msg());
                    }
                } else {
                    $this->logger->error("\aportela\ScraperLyrics\SourceProviders\Genius::getLink - Error: invalid HTTP content type", [$response->getContentType()]);
                    throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Invalid HTTP content type: " . $response->getContentType());
                }
            } else {
                $this->logger->error("\aportela\ScraperLyrics\SourceProviders\Genius::getLink - Error: empty body");
                throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Error: empty body");
            }
        } elseif ($response->code == 403) {
            // TODO: logger
            $this->logger->error("\aportela\ScraperLyrics\SourceProviders\Genius::getLink - Error: invalid HTTP response code: {$response->code}");
            throw new \aportela\ScraperLyrics\Exception\HTTPException("Invalid HTTP response code: {$response->code}");
        } else {
            $this->logger->error("\aportela\ScraperLyrics\SourceProviders\Genius::getLink - Error: invalid HTTP response code: {$response->code}");
            throw new \aportela\ScraperLyrics\Exception\HTTPException("Invalid HTTP response code: {$response->code}");
        }
    }

    public function scrap(string $title, string $artist): string
    {
        // get cookies so that Genius does not realize so easily that we are using a script and not a browser
        // I can't guarantee it will always work, but the request will be less suspicious in a quick analysis
        $response = $this->http->HEAD("https://genius.com/");
        $link = $this->getLink($title, $artist);
        $this->http->setReferer("https://genius.com/search?" . http_build_query(["q" => sprintf("\"%s\" \"%s\"", $title, $artist)]));
        $response = $this->http->GET($link);
        if ($response->code == 200) {
            if (!empty($response->body)) {
                libxml_use_internal_errors(true);
                $doc = new \DomDocument();
                if ($doc->loadHTML(str_ireplace(array("<br>", "<br/>", "<br />"), PHP_EOL, $response->body))) {
                    $xpath = new \DOMXPath($doc);
                    $expression = '//div[@data-lyrics-container="true"]';
                    $nodes = $xpath->query($expression);
                    if ($nodes !== false && $nodes->count() > 0) {
                        // TODO: trim intro (first line of first node textContent)
                        /** example:
                         * [textContent] => 502 ContributorsTranslationsDeutschTürkçeไทย (Thai)EspañolPortuguêsفارسیFrançaisPolskiРусский (Russian)ČeskyBohemian Rhapsody LyricsWidely considered to be one of the greatest songs of all time, “Bohemian Rhapsody” was the first single released from Queen’s fourth studio album, A Night at the Opera. It became an international success… Read More [Intro]
                         */
                        $data = null;
                        foreach ($nodes as $node) {
                            if (isset($node->textContent) && is_string($node->textContent)) {
                                $data .= mb_trim($node->textContent) . PHP_EOL;
                            }
                        }
                        if (is_string($data)) {
                            $data = mb_trim($data);
                        }
                        if (!empty($data)) {
                            return ($data);
                        } else {
                            $this->logger->error("\aportela\ScraperLyrics\SourceProviders\Genius::scrap - Error: empty lyrics");
                            throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Empty lyrics");
                        }
                    } else {
                        $this->logger->error("\aportela\ScraperLyrics\SourceProviders\Genius::scrap - Error: missing html xpath nodes", [$expression]);
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Missing html xpath nodes: {$expression}");
                    }
                } else {
                    $this->logger->error("\aportela\ScraperLyrics\SourceProviders\Genius::scrap - Error: invalid html body");
                    throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Invalid HTML body");
                }
            } else {
                $this->logger->error("\aportela\ScraperLyrics\SourceProviders\Genius::scrap - Error: empty body");
                throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Error: empty body");
            }
        } else {
            $this->logger->error("\aportela\ScraperLyrics\SourceProviders\Genius::scrap - Error: invalid HTTP response code: {$response->code}");
            throw new \aportela\ScraperLyrics\Exception\HTTPException("Invalid HTTP response code: {$response->code}");
        }
    }
}
