<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TomcatController extends Controller
{
    public function isRunning()
    {
        try {
            $host = 'localhost';
            $port = 8080;
            $socket = socket_create(AF_INET, SOCK_STREAM, 0);
            $result = socket_connect($socket, $host, $port);

            if ($result === true) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}
