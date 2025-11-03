<?php

namespace aportela\ScraperLyrics\SourceProviders;

final class SearchEngineDuckDuckGo extends BaseProvider
{
    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        parent::__construct($logger, null);
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
                    if (! empty($match[1])) {
                        $data = $this->parseHTMLCRLF($match[1]);
                        $data = $this->parseHTMLUnicode($data);
                        $data = mb_trim($data);
                        if (!empty($data)) {
                            return ($data);
                        } else {
                            $this->logger->error("\aportela\ScraperLyrics\SourceProviders\SearchEngineDuckDuckGo::scrap - Error: empty lyrics");
                            throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("Empty lyrics");
                        }
                    } else {
                        throw new \aportela\ScraperLyrics\Exception\InvalidSourceProviderAPIResponse("");
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
