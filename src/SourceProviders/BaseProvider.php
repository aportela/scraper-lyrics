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
        if (!in_array("libxml", $loadedExtensions)) {
            $this->logger->critical("\aportela\ScraperLyrics\SourceProviders\BaseProvider::__construct - Error: libxml extension not found", $loadedExtensions);
            throw new \aportela\ScraperLyrics\Exception\ExtensionMissingException("missing libxml extension, loaded extensions: " . implode(", ", $loadedExtensions));
        } elseif (!in_array("SimpleXML", $loadedExtensions)) {
            $this->logger->critical("MusicBrainzWrapper::__construct ERROR: SimpleXML extension not found", $loadedExtensions);
            throw new \aportela\ScraperLyrics\Exception\ExtensionMissingException("missing simplexml extension, loaded extensions: " . implode(", ", $loadedExtensions));
        }
        $this->http = new \aportela\HTTPRequestWrapper\HTTPRequest($this->logger, $customUserAgent);
    }

    abstract public function scrap(string $title, string $artist): string;

    public function __destruct()
    {
    }
}
