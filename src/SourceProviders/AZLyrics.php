<?php

namespace aportela\ScraperLyrics\SourceProviders;

final class AZLyrics extends BaseProvider
{
    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        // fake user agent as a real browser
        parent::__construct($logger, \aportela\HTTPRequestWrapper\UserAgent::EDGE_WINDOWS_10->value);
    }

    /**
     * @return array<string,string>
     */
    private function getInputHidden(): array
    {
        $this->http->setReferer("https://www.azlyrics.com/");
        $response = $this->http->GET("https://www.azlyrics.com/geo.js");
        if ($response->code == 200) {
            if (!empty($response->body)) {
                $pattern1 = '/ep\.setAttribute\("name", "(\w+)"\);/';
                if (preg_match_all($pattern1, $response->body, $nameMatches)) {
                    $pattern2 = '/ep\.setAttribute\("value", "(\w+)"\);/';
                    if (preg_match_all($pattern2, $response->body, $valueMatches)) {
                        return (["name" => $nameMatches[1][0], "value" => $valueMatches[1][0]]);
                    } else {
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse('/ep\.setAttribute\("value", "(\w+)"\);/');
                    }
                } else {
                    throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse('/ep\.setAttribute\("name", "(\w+)"\);/');
                }
            } else {
                throw new \aportela\ScraperLyrics\Exception\HTTPException("Invalid HTTP (empty) body");
            }
        } else {
            throw new \aportela\ScraperLyrics\Exception\HTTPException("Invalid HTTP response code: " . $response->code);
        }
    }


    private function getLink(string $title, string $artist): string
    {
        $input = $this->getInputHidden();
        $response = $this->http->GET("https://www.azlyrics.com/search/", ["q" => sprintf("\"%s\" \"%s\"", $title, $artist), $input["name"] => $input["value"]]);
        if ($response->code == 200) {
            if (!empty($response->body)) {
                $doc = new \DomDocument();
                libxml_use_internal_errors(true);
                if ($doc->loadHTML($response->body)) {
                    $xpath = new \DOMXPath($doc);
                    $nodes = $xpath->query('//table/tr/td/a');
                    if ($nodes != false) {
                        if ($nodes->count() > 0) {
                            return ($nodes[0]->getAttribute("href"));
                        } else {
                            throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse(sprintf("HTML Nodes %s not found", '//form[@class="search"]/input[@type="hidden"]'));
                        }
                    } else {
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse(sprintf("HTML Nodes %s not found", '//form[@class="search"]/input[@type="hidden"]'));
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
        $link = $this->getLink($title, $artist);
        $this->http->setReferer("https://search.azlyrics.com/search.php");
        $response = $this->http->GET($link);
        if ($response->code == 200) {
            if (!empty($response->body)) {
                $doc = new \DomDocument();
                if ($doc->loadHTML(str_ireplace(array("<br>", "<br/>", "<br />"), "", $response->body))) {
                    $xpath = new \DOMXPath($doc);
                    $nodes = $xpath->query('//div[@class="col-xs-12 col-lg-8 text-center"]/div');
                    if ($nodes != false) {
                        if ($nodes->count() == 6) {
                            $data = trim($nodes[4]->textContent);
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
