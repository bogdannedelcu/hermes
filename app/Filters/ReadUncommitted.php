<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ReadUncommitted implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $db = \Config\Database::connect();
        $db->query("SET SESSION TRANSACTION ISOLATION LEVEL READ UNCOMMITTED");
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
