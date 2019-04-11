<?php 

namespace App\Service;

class ArdmoreService
{
    public static function connect()
    {
        $connAPPS = oci_connect(env('DB_USERNAME'), env('DB_PASSWORD'), env('DB_HOST').':'.env('DB_PORT').'/'.env('DB_DATABASE'));
        if (!$connAPPS) {
            $e = oci_error();
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }

        return $connAPPS;
    }
}