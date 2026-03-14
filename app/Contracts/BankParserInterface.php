<?php

namespace App\Contracts;

use App\DTOs\ParseResult;

interface BankParserInterface
{
    public function parse(string $rawBody): ParseResult;
}
