<?php

namespace PhpExt\Routes\Enums;

use MyCLabs\Enum\Enum;

class HttpMethodEnum extends Enum
{
    const POST = 'POST';
    const GET = 'GET';
    const PATCH = 'PATCH';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
}
