<?php

use App\Services\Parsers\AcmeBankParser;
use App\Services\Parsers\FoodicsBankParser;

return [
    'parsers' => [
        'foodics' => FoodicsBankParser::class,
        'acme' => AcmeBankParser::class,
    ],
];
