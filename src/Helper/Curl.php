<?php

namespace Nails\Cli\Helper;

final class Curl
{
    /**
     * Returns the User Agent string to use for requests
     *
     * @return string
     */
    private function getUserAgent()
    {
        return 'Nails Command Line Tool version ' . Updates::getCurrentVersion();
    }

    // --------------------------------------------------------------------------

    /**
     * Basic GET request
     *
     * @param string $sUrl The URL to fetch
     *
     * @return bool|string
     */
    public static function get($sUrl)
    {
        $oCurl = curl_init();
        curl_setopt($oCurl, CURLOPT_URL, $sUrl);
        curl_setopt($oCurl, CURLOPT_USERAGENT, static::getUserAgent());
        curl_setopt($oCurl, CURLOPT_HEADER, false);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, true);
        $sResult = curl_exec($oCurl);
        curl_close($oCurl);

        return $sResult;
    }
}
