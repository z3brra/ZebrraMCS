<?php

namespace App\Service;

use App\Http\Error\ApiException;
use Symfony\Component\HttpFoundation\Request;

class RequestHelper
{
    public static function readPage(Request $request): int
    {
        $page = $request->query->get('page');
        if ($page === null) {
            return 1;
        }
        if (!ctype_digit((string) $page) || (int) $page < 1) {
            throw ApiException::badRequest('Invalid "page" parameter');
        }
        return (int) $page;
    }

    public static function readLimit(Request $request): int
    {
        $limit = $request->query->get('limit');
        if ($limit === null) {
            return 20;
        }
        if (!ctype_digit((string) $limit) || (int) $limit < 1) {
            throw ApiException::badRequest('Invalid "limit" parameter');
        }
        return min(100, (int) $limit);
    }
}

?>