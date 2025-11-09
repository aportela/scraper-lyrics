<?php

declare(strict_types=1);

namespace aportela\ScraperLyrics\SourceProviders;

final class SearchEngineDuckDuckGo extends BaseProvider
{
    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    public function scrap(string $title, string $artist): string
    {
        // obtain possible cookies (if necessary)
        $this->http->HEAD("https://duckduckgo.com/");
        // set referer from root search domain
        $this->http->setReferer("https://duckduckgo.com/");

        $httpResponse = $this->http->GET("https://duckduckgo.com/a.js", ["s" => "lyrics", "from" => "lyrics", "ta" => $artist, "tl" => $title]);
        if ($httpResponse->code === 200) {
            if (!in_array($httpResponse->body, [null, '', '0'], true)) {
                $pattern = '/DDG.duckbar.add_array\(\[\{"data":\[\{"Abstract":"(.*)","AbstractSource":"Musixmatch"/';
                if (preg_match($pattern, $httpResponse->body, $match)) {
                    if ($match[1] !== '' && $match[1] !== '0') {
                        $data = $this->parseHTMLCRLF($match[1]);
                        $data = $this->parseHTMLUnicode($data);
                        $data = mb_trim($data);
                        if ($data !== '' && $data !== '0') {
                            return ($data);
                        } else {
                            $this->logger->error(\aportela\ScraperLyrics\SourceProviders\SearchEngineDuckDuckGo::class . '::scrap - Error: empty lyrics');
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
            throw new \aportela\ScraperLyrics\Exception\HTTPException("Invalid HTTP response code: " . $httpResponse->code);
        }
    }
}
