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
        $response = $this->http->GET("https://duckduckgo.com/a.js", ["s" => "lyrics", "from" => "lyrics", "ta" => $artist, "tl" => $title]);
        if ($response->code == 200) {
            if (!empty($response->body)) {
                $pattern = '/DDG.duckbar.add_array\(\[\{"data":\[\{"Abstract":"(.*)","AbstractSource":"Musixmatch"/';
                if (preg_match($pattern, $response->body, $match)) {
                    if (count($match) == 2) {
                        $data = null;
                        foreach (explode(PHP_EOL, str_ireplace(array("<br>", "<br/>", "<br />"), PHP_EOL, $match[1])) as $line) {
                            $data .= trim($line) . PHP_EOL;
                        };
                        if (!empty($data)) {
                            return (json_decode($data));
                        } else {
                            throw new \aportela\ScraperLyrics\Exception\NotFoundException("");
                        }
                    } else {
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse(sprintf("JS array %s not found", 'DDG.duckbar.add_array'));
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
