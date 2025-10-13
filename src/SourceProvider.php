<?php

namespace aportela\ScraperLyrics;

enum SourceProvider
{
    case SEARCH_ENGINE_DUCKDUCKGO;
    case MUSIXMATCH;
    case LYRICS_MANIA;
    case GENIUS;
    case AZLYRICS;
}
