<?php

namespace Bu\Server\GraphQL\Mutations;

use Bu\Server\Models\License;

class LicenseMutations
{
    public function delete($_, array $args): bool
    {
        $id = $args['id'] ?? null;
        if (!$id) {
            return false;
        }

        $license = License::find($id);
        if (!$license) {
            return false;
        }

        return (bool) $license->delete();
    }
}
