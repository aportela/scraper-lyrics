<?php

declare(strict_types=1);

namespace aportela\ScraperLyrics\SourceProviders;

abstract class BaseProvider implements ISourceProvider
{
    protected \aportela\HTTPRequestWrapper\HTTPRequest $http;

    /**
     * @param array<string> $requiredExtensions
     */
    public function __construct(protected \Psr\Log\LoggerInterface $logger, ?string $customUserAgent = null, array $requiredExtensions = ["dom", "libxml", "SimpleXML"])
    {
        $loadedExtensions = get_loaded_extensions();
        foreach ($requiredExtensions as $requiredExtension) {
            if (!in_array("dom", $loadedExtensions)) {
                $this->logger->critical(sprintf(\aportela\ScraperLyrics\SourceProviders\BaseProvider::class . '::__construct - Error: %s extension not found', $requiredExtension), $loadedExtensions);
                throw new \aportela\ScraperLyrics\Exception\ExtensionMissingException(sprintf('missing %s extension, loaded extensions: ', $requiredExtension) . implode(", ", $loadedExtensions));
            }
        }

        $this->http = new \aportela\HTTPRequestWrapper\HTTPRequest($this->logger, $customUserAgent);
    }

    abstract public function scrap(string $title, string $artist): string;

    public function parseHTMLCRLF(string $html): string
    {
        $data = "";
        if ($html !== '' && $html !== '0') {
            foreach (
                explode(
                    PHP_EOL,
                    str_ireplace(
                        [
                            "<br>",
                            "<br/>",
                            "<br />",
                        ],
                        PHP_EOL,
                        $html
                    )
                ) as $line
            ) {
                $data .= mb_trim($line) . PHP_EOL;
            };
        }

        return ($data);
    }

    public function parseHTMLUnicode(string $html): string
    {
        $data = "";
        if ($html !== '' && $html !== '0') {
            $data = preg_replace_callback(
                '/\\\\u([0-9a-fA-F]{4})/',
                fn($match): string => mb_convert_encoding(
                    pack('H*', $match[1]),
                    'UTF-8',
                    'UCS-2BE'
                ),
                $html
            );
        }

        return (strval($data));
    }
}
