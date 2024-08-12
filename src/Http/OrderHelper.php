<?php

namespace Braintacle\Http;

/**
 * Extract and validate "order" and "direction" URL parameters
 */
class OrderHelper
{
    /**
     * @return list{string, "asc"|"desc"}
     */
    public function __invoke(array $params, string $defaultOrder, string $defaultDirection = 'asc'): array
    {
        $order = $params['order'] ?? null ?: $defaultOrder;
        $direction = $params['direction'] ?? null;
        if ($direction != 'asc' && $direction != 'desc') {
            $direction = $defaultDirection;
        }

        return [$order, $direction];
    }
}
