<?php

namespace Ilbee\CSVResponse;

interface CSVResponseInterface
{
    public const COMMA = ',';
    public const SEMICOLON = ';';
    public const DOUBLEQUOTE = '"';
    /**
     * @deprecated No escape character is used anymore: output follows RFC 4180
     *             (quotes are doubled). Kept for backward compatibility only.
     */
    public const DOUBLESLASH = '\\';
}
