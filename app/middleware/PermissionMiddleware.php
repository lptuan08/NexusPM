<?php

namespace App\middleware;

use App\interfaces\MiddlewareInterface;
use App\helpers\AuthHelper;
use Exception;

class PermissionMiddleware implements MiddlewareInterface
{

    private array $permissions;

    public function __construct(string ...$permissions)
    {
        $this->permissions = $permissions;
    }

    public function handle()
    {
        if (!AuthHelper::canAny($this->permissions)) {
            throw new Exception('Bạn không có quyền thực hiện chức năng này.', 403);
        }
    }
}
