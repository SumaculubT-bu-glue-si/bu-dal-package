<?php

namespace Bu\Server\GraphQL\Queries;

use Bu\Server\Models\ServiceSubscription;

class SubscriptionQuery
{
    public function __invoke($_, array $args)
    {
        return ServiceSubscription::with([
            'licenses.assignedEmployee',
        ])->findOrFail($args['id']);
    }

    public function getSubscription($_, array $args)
    {
        return ServiceSubscription::with(['licenses.assignedEmployee'])->findOrFail($args['id']);
    }
}
