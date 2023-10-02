<?php

namespace aportela\ScraperLyrics\SourceProviders;

class BaseProvider implements ISourceProvider
{
    protected \Psr\Log\LoggerInterface $logger;
    protected \aportela\HTTPRequestWrapper\HTTPRequest $http;

    public function __construct(\Psr\Log\LoggerInterface $logger, ?string $customUserAgent)
    {
        $this->logger = $logger;
        $this->logger->debug("ScraperLyrics\SourceProviders\BaseProvider::__construct");
        $loadedExtensions = get_loaded_extensions();
        if (!in_array("libxml", $loadedExtensions)) {
            $this->logger->critical("ScraperLyrics\SourceProviders\BaseProvider::__construct ERROR: libxml extension not found");
            throw new \aportela\ScraperLyrics\Exception\LibXMLMissingException("loaded extensions: " . implode(", ", $loadedExtensions));
        } elseif (!in_array("SimpleXML", $loadedExtensions)) {
            $this->logger->critical("ScraperLyrics\SourceProviders\BaseProvider::__construct ERROR: SimpleXML extension not found");
            throw new \aportela\ScraperLyrics\Exception\SimpleXMLMissingException("loaded extensions: " . implode(", ", $loadedExtensions));
        } else {
            $this->logger->debug("ScraperLyrics\SourceProviders\BaseProvider::__construct");
            $this->http = new \aportela\HTTPRequestWrapper\HTTPRequest($this->logger, $customUserAgent ?? "");
        }
    }

    public function scrap(string $title, string $artist): string
    {
        return ("");
    }

    public function __destruct()
    {
        $this->logger->debug("ScraperLyrics\SourceProviders\BaseProvider::__destruct");
    }
}
