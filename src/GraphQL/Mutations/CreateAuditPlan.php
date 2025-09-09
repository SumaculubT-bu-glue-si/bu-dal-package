<?php

namespace Bu\Server\GraphQL\Mutations;

use Bu\Server\Models\AuditPlan;
use Bu\Server\Models\AuditAssignment;
use Bu\Server\Models\AuditAsset;
use Bu\Server\Models\Asset;
use Bu\Server\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Bu\Server\Services\AuditNotificationService;

class CreateAuditPlan
{
    public function __invoke($rootValue, array $args)
    {
        try {
            // Enhanced validation with detailed error messages
            $validationErrors = [];
            
            // Check for required fields
            if (empty($args['name']) || trim($args['name']) === '') {
                $validationErrors[] = 'Audit plan name is required and cannot be empty';
            }
            
            if (empty($args['start_date'])) {
                $validationErrors[] = 'Start date is required';
            } elseif (!strtotime($args['start_date'])) {
                $validationErrors[] = 'Start date must be a valid date format (YYYY-MM-DD)';
            }
            
            if (empty($args['due_date'])) {
                $validationErrors[] = 'Due date is required';
            } elseif (!strtotime($args['due_date'])) {
                $validationErrors[] = 'Due date must be a valid date format (YYYY-MM-DD)';
            }
            
            // Check if due date is after start date
            if (!empty($args['start_date']) && !empty($args['due_date']) && 
                strtotime($args['due_date']) <= strtotime($args['start_date'])) {
                $validationErrors[] = 'Due date must be after the start date';
            }
            
            if (empty($args['locations']) || !is_array($args['locations']) || count($args['locations']) === 0) {
                $validationErrors[] = 'At least one location is required';
            }
            
            if (empty($args['auditors']) || !is_array($args['auditors']) || count($args['auditors']) === 0) {
                $validationErrors[] = 'At least one auditor is required';
            }
            
            // If there are validation errors, throw them all at once
            if (!empty($validationErrors)) {
                throw new \Exception('Validation failed: ' . implode('; ', $validationErrors));
            }
            
            // Validate that locations exist
            $locationIds = $args['locations'];
            $existingLocations = \Bu\Server\Models\Location::whereIn('id', $locationIds)->pluck('id')->toArray();
            $missingLocations = array_diff($locationIds, $existingLocations);
            if (!empty($missingLocations)) {
                throw new \Exception('Invalid location IDs: ' . implode(', ', $missingLocations));
            }
            
            // Validate that auditors exist
            $auditorIds = $args['auditors'];
            $existingAuditors = \Bu\Server\Models\Employee::whereIn('id', $auditorIds)->pluck('id')->toArray();
            $missingAuditors = array_diff($auditorIds, $existingAuditors);
            if (!empty($missingAuditors)) {
                throw new \Exception('Invalid auditor IDs: ' . implode(', ', $missingAuditors));
            }

            return DB::transaction(function () use ($args) {
                // Get or create a default user for testing
                $userId = Auth::id();
                if (!$userId) {
                    // For testing purposes, get the first user or create one
                    $user = \Bu\Server\Models\User::first();
                    if (!$user) {
                        throw new \Exception('No users found in the system. Please run the database seeder first.');
                    }
                    $userId = $user->id;
                }

                // Create the audit plan
                $auditPlan = AuditPlan::create([
                    'name' => trim($args['name']),
                    'start_date' => $args['start_date'],
                    'due_date' => $args['due_date'],
                    'status' => 'Planning',
                    'created_by' => $userId,
                    'description' => $args['description'] ?? null,
                ]);

                // Create audit assignments - one assignment per auditor-location combination
                $assignments = [];

                foreach ($args['locations'] as $locationId) {
                    foreach ($args['auditors'] as $auditorId) {
                        $assignments[] = [
                            'audit_plan_id' => $auditPlan->id,
                            'location_id' => $locationId,
                            'auditor_id' => $auditorId,
                            'status' => 'Assigned',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                if (!empty($assignments)) {
                    AuditAssignment::insert($assignments);

                    // Send notification emails to all assigned employees
                    try {
                        $notificationService = new AuditNotificationService();
                        $notificationsSent = $notificationService->sendInitialNotifications(
                            $auditPlan,
                            $args['auditors'],
                            $args['locations']
                        );

                        Log::info('Audit assignments created and notifications sent', [
                            'audit_plan_id' => $auditPlan->id,
                            'total_assignments' => count($assignments),
                            'auditors' => $args['auditors'],
                            'locations' => $args['locations'],
                            'notifications_sent' => $notificationsSent
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to send audit notifications', [
                            'audit_plan_id' => $auditPlan->id,
                            'error' => $e->getMessage()
                        ]);
                        // Continue with audit plan creation even if notifications fail
                    }
                } else {
                    Log::warning('No audit assignments created', [
                        'audit_plan_id' => $auditPlan->id,
                        'auditors' => $args['auditors'],
                        'locations' => $args['locations']
                    ]);
                }

                // Get all assets from the selected locations
                $locationNames = DB::table('locations')
                    ->whereIn('id', $args['locations'])
                    ->pluck('name');

                if ($locationNames->isEmpty()) {
                    throw new \Exception('No locations found with the provided IDs');
                }

                // Get assets with employee information to properly populate user names
                $assets = Asset::with('employee')
                    ->whereIn('location', $locationNames)
                    ->get();

                // Create audit assets
                $auditAssets = [];
                foreach ($assets as $asset) {
                    // Get the employee name from the user_id relationship
                    // If asset has no user_id, set both original_user and current_user to null
                    // If asset has user_id, get the employee name from the employee table
                    $originalUserName = null;
                    $currentUserName = null;

                    if ($asset->user_id && $asset->employee) {
                        $originalUserName = $asset->employee->name;
                        $currentUserName = $asset->employee->name; // Store the employee name for consistency
                    }

                    $auditAssets[] = [
                        'audit_plan_id' => $auditPlan->id,
                        'asset_id' => $asset->id,
                        'original_location' => $asset->location,
                        'original_user' => $originalUserName, // Employee name or null if no user
                        'current_location' => $asset->location, // Initialize with current location
                        'current_user' => $currentUserName, // Employee name or null if no user
                        'current_status' => $asset->status, // Use actual asset status instead of enum
                        'audit_status' => false, // New audit status column
                        'resolved' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($auditAssets)) {
                    // Log the audit assets being created for debugging
                    Log::info('Creating audit assets:', [
                        'count' => count($auditAssets),
                        'sample' => array_slice($auditAssets, 0, 2)
                    ]);

                    AuditAsset::insert($auditAssets);

                    Log::info('Audit assets created successfully', [
                        'audit_plan_id' => $auditPlan->id,
                        'total_assets' => count($auditAssets)
                    ]);
                } else {
                    Log::warning('No audit assets created - no assets found in selected locations', [
                        'audit_plan_id' => $auditPlan->id,
                        'locations' => $locationNames->toArray()
                    ]);
                }

                // Log the creation
                AuditLog::log(
                    $auditPlan->id,
                    'Created',
                    $userId,
                    null,
                    null,
                    ['name' => $auditPlan->name, 'locations' => $locationNames->toArray()],
                    "Audit plan '{$auditPlan->name}' created with " . count($assets) . " assets"
                );

                return $auditPlan->load(['assignments.location', 'assignments.auditor', 'auditAssets.asset']);
            });
        } catch (\Exception $e) {
            Log::error('CreateAuditPlan error: ' . $e->getMessage(), [
                'args' => $args,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
