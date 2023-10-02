<?php

namespace aportela\ScraperLyrics;

enum SourceProvider
{
    case SEARCH_ENGINE_GOOGLE;
    case SEARCH_ENGINE_BING;
    case SEARCH_ENGINE_DUCKDUCKGO;
    case MUSIXMATCH;
    case LYRICS_MANIA;
    case GENIUS;
}
