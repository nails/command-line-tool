<?php

namespace Nails\Cli\Helper;

final class Directory
{
    /**
     * Normalizes *nix style forward slashes with the system's DIRECTORY_SEPARATOR
     *
     * @param $sPath
     *
     * @return string
     */
    public static function normalize($sPath)
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $sPath);
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether a directory is empty or not
     *
     * @param string $sPath The path to query
     *
     * @return bool
     */
    public static function isEmpty($sPath)
    {
        if (!is_dir($sPath)) {
            return true;
        }

        $hDir = opendir($sPath);
        while (false !== ($sEntry = readdir($hDir))) {
            if ($sEntry != '.' && $sEntry != '..') {
                return false;
            }
        }

        return true;
    }
}
