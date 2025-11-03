<?php

namespace aportela\ScraperLyrics\SourceProviders;

abstract class BaseProvider implements ISourceProvider
{
    protected \Psr\Log\LoggerInterface $logger;
    protected \aportela\HTTPRequestWrapper\HTTPRequest $http;

    public function __construct(\Psr\Log\LoggerInterface $logger, ?string $customUserAgent = null)
    {
        $this->logger = $logger;
        $loadedExtensions = get_loaded_extensions();
        if (!in_array("dom", $loadedExtensions)) {
            $this->logger->critical("\aportela\ScraperLyrics\SourceProviders\BaseProvider::__construct - Error: dom extension not found", $loadedExtensions);
        } elseif (!in_array("libxml", $loadedExtensions)) {
            $this->logger->critical("\aportela\ScraperLyrics\SourceProviders\BaseProvider::__construct - Error: libxml extension not found", $loadedExtensions);
            throw new \aportela\ScraperLyrics\Exception\ExtensionMissingException("missing libxml extension, loaded extensions: " . implode(", ", $loadedExtensions));
        } elseif (!in_array("SimpleXML", $loadedExtensions)) {
            $this->logger->critical("MusicBrainzWrapper::__construct ERROR: SimpleXML extension not found", $loadedExtensions);
            throw new \aportela\ScraperLyrics\Exception\ExtensionMissingException("missing simplexml extension, loaded extensions: " . implode(", ", $loadedExtensions));
        }
        $this->http = new \aportela\HTTPRequestWrapper\HTTPRequest($this->logger, $customUserAgent);
    }

    abstract public function scrap(string $title, string $artist): string;

    public function __destruct() {}

    public function parseHTMLCRLF(string $html): string
    {
        $data = "";
        if (! empty($html)) {
            foreach (
                explode(
                    PHP_EOL,
                    str_ireplace(
                        [
                            "<br>",
                            "<br/>",
                            "<br />"
                        ],
                        PHP_EOL,
                        $html
                    )
                )
                as $line
            ) {
                $data .= mb_trim($line) . PHP_EOL;
            };
        }
        return ($data);
    }

    public function parseHTMLUnicode(string $html): string
    {
        $data = "";
        if (! empty($html)) {
            $data = preg_replace_callback(
                '/\\\\u([0-9a-fA-F]{4})/',
                function ($match) {
                    return mb_convert_encoding(
                        pack('H*', $match[1]),
                        'UTF-8',
                        'UCS-2BE'
                    );
                },
                $html
            );
        }
        return ($data);
    }
}
