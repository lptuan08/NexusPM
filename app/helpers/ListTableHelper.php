<?php

namespace App\helpers;

use App\core\Config;

class ListTableHelper
{
    private const DEFAULT_CONFIG = [
        'per_page' => 10,
        'height_offset' => '240px',
        'mobile_height_offset' => '220px',
        'max_visible_pages' => 5,
    ];

    public static function config(): array
    {
        $config = Config::load('ui')['paginated_table'] ?? [];

        return array_merge(self::DEFAULT_CONFIG, is_array($config) ? $config : []);
    }

    public static function perPage(): int
    {
        return max(1, (int) self::config()['per_page']);
    }
}
