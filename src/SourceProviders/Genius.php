<?php

namespace aportela\ScraperLyrics\SourceProviders;

final class Genius extends BaseProvider
{
    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        parent::__construct($logger, "");
    }

    private function getLink(string $title, string $artist): string
    {
        $response = $this->http->GET("https://genius.com/api/search/multi", ["per_page" => 1, "q" => sprintf("\"%s\" \"%s\"", $title, $artist)]);
        if ($response->code == 200) {
            if (!empty($response->body)) {
                if ($response->is(\aportela\HTTPRequestWrapper\ContentType::JSON)) {
                    $json = json_decode($response->body);
                    if ($json !== null && json_last_error() === JSON_ERROR_NONE) {
                        if ($json->meta && $json->meta->status == 200) {
                            if ($json->response && $json->response->sections && is_array($json->response->sections)) {
                                if ($json->response->sections[0]->hits && is_array($json->response->sections[0]->hits)) {
                                    foreach ($json->response->sections[0]->hits as $hit) {
                                        if ($hit->type == "song" && $hit->result && $hit->result->url && filter_var($hit->result->url, FILTER_VALIDATE_URL)) {
                                            return ($hit->result->url);
                                        }
                                    }
                                    throw new \aportela\ScraperLyrics\Exception\NotFoundException("");
                                } else {
                                    throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse(sprintf("Response sections hits array not found"));
                                }
                            } else {
                                throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse(sprintf("Response sections array not found"));
                            }
                        } else {
                            throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse(sprintf("Meta status != 200", $json->meta->status));
                        }
                    } else {
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Invalid JSON body");
                    }
                } else {
                    throw new \aportela\ScraperLyrics\Exception\HTTPException("Invalid HTTP content type: " . $response->getContentType());
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
                    // lyric are contained on multiple childs of <div data-lyrics-container>
                    $nodes = $xpath->query('//div[@data-lyrics-container="true"]');
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
