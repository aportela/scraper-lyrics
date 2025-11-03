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
                        if (isset($json->meta) && isset($json->meta->status) && $json->meta->status == 200) {
                            if (isset($json->response) && isset($json->response->sections) && is_array($json->response->sections)) {
                                if (isset($json->response->sections[0]->hits) && is_array($json->response->sections[0]->hits)) {
                                    foreach ($json->response->sections[0]->hits as $hit) {
                                        if (isset($hit->type) && $hit->type == "song" && isset($hit->result) && isset($hit->result->url) && is_string($hit->result->url) && filter_var($hit->result->url, FILTER_VALIDATE_URL)) {
                                            return ($hit->result->url);
                                        }
                                    }
                                    // TODO: logger
                                    throw new \aportela\ScraperLyrics\Exception\NotFoundException("");
                                } else {
                                    // TODO: logger
                                    throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Response sections hits array not found");
                                }
                            } else {
                                // TODO: logger
                                throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Response sections array not found");
                            }
                        } else {
                            // TODO: logger
                            throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse(sprintf("Meta status != 200 (%d)", $json->meta->status));
                        }
                    } else {
                        // TODO: logger
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Invalid JSON body");
                    }
                } else {
                    // TODO: logger
                    throw new \aportela\ScraperLyrics\Exception\HTTPException("Invalid HTTP content type: " . $response->getContentType());
                }
            } else {
                // TODO: logger
                throw new \aportela\ScraperLyrics\Exception\HTTPException("Invalid HTTP (empty) body");
            }
        } elseif ($response->code == 403) {
            // TODO: logger
            throw new \aportela\ScraperLyrics\Exception\HTTPException("Invalid HTTP response code: " . $response->code . " (cloudflare protecction ?)");
        } else {
            // TODO: logger
            throw new \aportela\ScraperLyrics\Exception\HTTPException("Invalid HTTP response code: " . $response->code);
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
                    $nodes = $xpath->query('//div[@data-lyrics-container="true"]');
                    if ($nodes != false) {
                        // TODO: trim intro (first line of first node textContent)
                        /** example:
                         * [textContent] => 502 ContributorsTranslationsDeutschTürkçeไทย (Thai)EspañolPortuguêsفارسیFrançaisPolskiРусский (Russian)ČeskyBohemian Rhapsody LyricsWidely considered to be one of the greatest songs of all time, “Bohemian Rhapsody” was the first single released from Queen’s fourth studio album, A Night at the Opera. It became an international success… Read More [Intro]
                         */
                        if ($nodes->count() > 0) {
                            $data = null;
                            foreach ($nodes as $key => $node) {
                                $data .= mb_trim($node->textContent) . PHP_EOL;
                            }
                            if (!empty($data)) {
                                return ($data);
                            } else {
                                throw new \aportela\ScraperLyrics\Exception\NotFoundException("");
                            }
                        } else {
                            throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse(sprintf("HTML Nodes %s not found", '//div[@data-lyrics-container="true"]'));
                        }
                    } else {
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse(sprintf("HTML Nodes %s not found", '//div[@data-lyrics-container="true"]'));
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
