<?php

namespace aportela\ScraperLyrics\SourceProviders;

final class SearchEngineDuckDuckGo extends BaseProvider
{
    // WARNING: (AT THIS TIME) this is REQUIRED/IMPORTANT, with another user agents (on GOOGLE) the search response is not the same (do not include lyrics!)
    private const USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36 Edg/116.0.1938.81";

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        parent::__construct($logger, self::USER_AGENT);
    }

    public function scrap(string $title, string $artist): string
    {
        $response = $this->http->GET(sprintf("https://duckduckgo.com/a.js?s=lyrics&from=lyrics&%s", http_build_query(["ta" => $artist, "tl" => $title])));
        if ($response->code == 200 && !empty($response->body)) {
            $pattern = '/DDG.duckbar.add_array\(\[\{"data":\[\{"Abstract":"(.*)","AbstractSource":"Musixmatch"/';
            if (preg_match($pattern, $response->body, $match)) {
                if (count($match) == 2) {
                    $data = null;
                    foreach (explode(PHP_EOL, str_ireplace(array("<br>", "<br/>", "<br />"), PHP_EOL, $match[1])) as $line) {
                        $data .= trim($line) . PHP_EOL;
                    };
                    if (!empty($data)) {
                        return ($data);
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
            throw new \aportela\ScraperLyrics\Exception\HTTPException("Invalid HTTP response code: " . $response->code);
        }
    }
}
