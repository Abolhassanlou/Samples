<?php

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}