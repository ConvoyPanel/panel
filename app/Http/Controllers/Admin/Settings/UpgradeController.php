<?php

namespace Convoy\Http\Controllers\Admin\Settings;

use Convoy\Http\Controllers\ApiController;
use Convoy\Services\Upgrade\UpgradeCheckService;
use Illuminate\Http\JsonResponse;

/** Settings → Upgrade: what Convoy 6 will do with this panel's data. Read-only. */
class UpgradeController extends ApiController
{
    public function __invoke(UpgradeCheckService $check): JsonResponse
    {
        return new JsonResponse(['data' => ['items' => $check->items()]]);
    }
}
