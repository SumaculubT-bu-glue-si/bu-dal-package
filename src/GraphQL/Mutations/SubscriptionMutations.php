<?php

namespace Bu\Server\GraphQL\Mutations;

use Bu\Server\Models\ServiceSubscription;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class SubscriptionMutations
{
    /**
     * Creates a new Subscription and associated Licenses.
     *
     * This method acts as a GraphQL resolver. It receives a single object containing
     * all the data for the new subscription and its related licenses. It then
     * creates the subscription record and uses the relationship to create
     * all the associated license records in a single transaction.
     *
     * @param  null  $root The object that contains the result from the parent field.
     * @param  array  $args The arguments passed to the field. We expect 'object' which contains all data.
     * @param  GraphQLContext  $context The GraphQL context for the request.
     * @return Subscription The newly created Subscription model instance with its licenses.
     * @throws \Exception If the data is missing or the creation fails.
     */
    public function create($root, array $args, GraphQLContext $context)
    {
        // Ensure the 'object' argument exists and is an array.
        if (!isset($args['object']) || !is_array($args['object'])) {
            throw new \InvalidArgumentException('The input object is required.');
        }

        $subscriptionData = $args['object'];

        // Extract licenses data from the input object before creating the subscription.
        // We use an empty array as a default to prevent errors if no licenses are provided.
        $licensesData = $subscriptionData['licenses'] ?? [];
        unset($subscriptionData['licenses']);

        // Normalize per-seat pricing fields to expected DB types/values.
        if (isset($subscriptionData['per_seat_currency'])) {
            // Accept JPY/USD (case-insensitive) and store as lowercase enum values defined in migration
            $currency = strtolower((string) $subscriptionData['per_seat_currency']);
            $subscriptionData['per_seat_currency'] = $currency === 'usd' ? 'usd' : 'jpy';
        }

        if (isset($subscriptionData['per_seat_monthly_price'])) {
            $subscriptionData['per_seat_monthly_price'] = is_numeric($subscriptionData['per_seat_monthly_price'])
                ? (int) $subscriptionData['per_seat_monthly_price']
                : null;
        }

        if (isset($subscriptionData['per_seat_yearly_price'])) {
            $subscriptionData['per_seat_yearly_price'] = is_numeric($subscriptionData['per_seat_yearly_price'])
                ? (int) $subscriptionData['per_seat_yearly_price']
                : null;
        }

        // Create the new Subscription record in the database.
        // Eloquent's create() method returns the new model instance.
        $subscription = ServiceSubscription::create($subscriptionData);

        // Check if there are licenses to create.
        if (!empty($licensesData)) {
            // Normalize used and assigned employee on create
            $normalized = array_map(function ($lic) {
                $payload = $lic;
                if (array_key_exists('used', $payload)) {
                    $payload['used'] = (bool) $payload['used'];
                }
                if (!empty($payload['assigned_employee']) && is_array($payload['assigned_employee'])) {
                    $ae = $payload['assigned_employee'];
                    $payload['assigned_employee_id'] = $ae['employee_id'] ?? ($ae['id'] ?? null);
                    unset($payload['assigned_employee']);
                }
                return $payload;
            }, $licensesData);

            // Use the licenses() relationship to create multiple licenses
            // and automatically associate them with the new subscription.
            $subscription->licenses()->createMany($normalized);
        }

        // Reload the subscription with its new licenses to ensure the response
        // contains the complete data, including the IDs for the licenses.
        $subscription->refresh();
        $subscription->load('licenses');

        // Return the complete subscription object.
        return $subscription;
    }

    /**
     * Update a subscription.
     *
     * @param  null  $_
     * @param  array<string, mixed>  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @return \App\Models\Subscription
     */
    public function update($_, array $args, GraphQLContext $context)
    {
        $subscriptionId = $args['id'];
        $inputData = $args['input'];

        /** @var ServiceSubscription $subscription */
        $subscription = ServiceSubscription::findOrFail($subscriptionId);

        // Extract licenses array before updating subscription
        $licensesData = $inputData['licenses'] ?? [];
        unset($inputData['licenses']);

        // Normalize per-seat pricing fields
        if (isset($inputData['per_seat_currency'])) {
            $currency = strtolower((string)$inputData['per_seat_currency']);
            $inputData['per_seat_currency'] = in_array($currency, ['usd', 'jpy']) ? $currency : 'jpy';
        }

        foreach (['per_seat_monthly_price', 'per_seat_yearly_price'] as $priceField) {
            if (isset($inputData[$priceField])) {
                $inputData[$priceField] = is_numeric($inputData[$priceField])
                    ? (int)$inputData[$priceField]
                    : null;
            }
        }

        // --- Update subscription base fields ---
        $subscription->fill($inputData);
        $subscription->save();

        // --- Handle license updates safely ---
        $existingLicenses = $subscription->licenses()->get()->keyBy('id');

        foreach ($licensesData as $licenseInput) {
            $licenseId = $licenseInput['id'] ?? null;

            // define fields allowed to update
            $updatable = [
                'account_id'      => $licenseInput['account_id'] ?? null,
                'unit_price'      => $licenseInput['unit_price'] ?? null,
                'currency'        => $licenseInput['currency'] ?? null,
                'billing_cycle'   => $licenseInput['billing_cycle'] ?? null,
                'billing_interval' => $licenseInput['billing_interval'] ?? null,
                'start_date'      => $licenseInput['start_date'] ?? null,
                'end_date'        => $licenseInput['end_date'] ?? null,
                'renewal_date'    => $licenseInput['renewal_date'] ?? null,
                'version'         => $licenseInput['version'] ?? null,
                'license_key'     => $licenseInput['license_key'] ?? null,
            ];

            if ($licenseId && isset($existingLicenses[$licenseId])) {
                // ✅ Update existing license while preserving 'used' and 'assigned_employee_id'
                $license = $existingLicenses[$licenseId];

                foreach ($updatable as $key => $value) {
                    if ($value !== null) {
                        $license->{$key} = $value;
                    }
                }

                // Keep used/assignment flags as-is unless explicitly changed
                if (array_key_exists('used', $licenseInput)) {
                    $license->used = (bool) $licenseInput['used'];
                }

                if (array_key_exists('assigned_employee', $licenseInput)) {
                    $assigned = $licenseInput['assigned_employee'];
                    if ($assigned) {
                        // accept either employee_id or id
                        $license->assigned_employee_id = $assigned['employee_id'] ?? ($assigned['id'] ?? null);
                    } else {
                        $license->assigned_employee_id = null;
                    }
                }

                $license->save();
            } else {
                // ✅ Create new license entry (newly added license)
                $payload = $updatable;
                // defaults
                $payload['service_subscription_id'] = $subscription->id;
                $payload['used'] = array_key_exists('used', $licenseInput) ? (bool) $licenseInput['used'] : false;
                if (!empty($licenseInput['assigned_employee'])) {
                    $assigned = $licenseInput['assigned_employee'];
                    $payload['assigned_employee_id'] = $assigned['employee_id'] ?? ($assigned['id'] ?? null);
                }

                $subscription->licenses()->create($payload);
            }
        }

        // --- Optionally handle deleted licenses ---
        $incomingIds = collect($licensesData)->pluck('id')->filter()->all();
        $licensesToDelete = $existingLicenses->keys()->diff($incomingIds);

        if ($licensesToDelete->isNotEmpty()) {
            $subscription->licenses()->whereIn('id', $licensesToDelete)->delete();
        }

        // --- Reload updated relationships ---
        $subscription->refresh();
        $subscription->load(['licenses.assignedEmployee', 'employees']);

        return $subscription;
    }
}
