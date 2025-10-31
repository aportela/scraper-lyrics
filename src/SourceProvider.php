<?php

namespace aportela\ScraperLyrics;

enum SourceProvider: string
{
    case SEARCH_ENGINE_DUCKDUCKGO = "duckduckgo";
    case MUSIXMATCH = "musicmatch";
    case LYRICS_MANIA = "lyricsmania";
    case GENIUS = "genius";
    case AZLYRICS = "azlyrics";
}
