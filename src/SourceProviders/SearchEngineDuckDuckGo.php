<?php

namespace aportela\ScraperLyrics\SourceProviders;

final class SearchEngineDuckDuckGo extends BaseProvider
{
    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        parent::__construct($logger, "");
    }

    public function scrap(string $title, string $artist): string
    {
        // obtain possible cookies (if necessary)
        $this->http->HEAD("https://duckduckgo.com/");
        // set referer from root search domain
        $this->http->setReferer("https://duckduckgo.com/");
        $response = $this->http->GET("https://duckduckgo.com/a.js", ["s" => "lyrics", "from" => "lyrics", "ta" => $artist, "tl" => $title]);
        if ($response->code == 200) {
            if (!empty($response->body)) {
                $pattern = '/DDG.duckbar.add_array\(\[\{"data":\[\{"Abstract":"(.*)","AbstractSource":"Musixmatch"/';
                if (preg_match($pattern, $response->body, $match)) {
                    $data = null;
                    foreach (explode(PHP_EOL, str_ireplace(array("<br>", "<br/>", "<br />"), PHP_EOL, $match[1])) as $line) {
                        $data .= trim($line) . PHP_EOL;
                    };
                    if (!empty($data)) {
                        $data = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
                            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
                        }, $data);
                        return ($data);
                    } else {
                        throw new \aportela\ScraperLyrics\Exception\NotFoundException("");
                    }
                } else {
                    throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse(sprintf("JS array %s not found", 'DDG.duckbar.add_array'));
                }
            } else {
                throw new \aportela\ScraperLyrics\Exception\HTTPException("Invalid HTTP (empty) body");
            }
        } else {
            throw new \aportela\ScraperLyrics\Exception\HTTPException("Invalid HTTP response code: " . $response->code);
        }
    }
}
