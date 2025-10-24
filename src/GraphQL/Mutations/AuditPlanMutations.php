<?php

namespace Bu\DAL\GraphQL\Mutations;

use Bu\DAL\Models\AuditPlan;
use Bu\DAL\Models\AuditAssignment;
use Bu\DAL\Models\AuditAsset;
use Bu\DAL\Models\Asset;
use Bu\DAL\Models\AuditLog;
use Bu\DAL\Database\Repositories\AuditPlanRepository;
use Bu\DAL\Database\Repositories\LocationRepository;
use Bu\DAL\Database\Repositories\EmployeeRepository;
use Bu\DAL\Database\DatabaseManager;
use Bu\DAL\Services\AuditNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuditPlanMutations
{
    public function __construct(
        private AuditPlanRepository $auditPlanRepository,
        private LocationRepository $locationRepository,
        private EmployeeRepository $employeeRepository,
        private DatabaseManager $databaseManager,
        private AuditNotificationService $auditNotificationService
    ) {}

    /**
     * Create a new audit plan.
     */
    public function createAuditPlan($rootValue, array $args)
    {
        return $this->databaseManager->transaction(function () use ($args) {
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
            if (
                !empty($args['start_date']) && !empty($args['due_date']) &&
                strtotime($args['due_date']) <= strtotime($args['start_date'])
            ) {
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
            $existingLocations = $this->locationRepository->whereIn('id', $locationIds)->pluck('id')->toArray();
            $missingLocations = array_diff($locationIds, $existingLocations);
            if (!empty($missingLocations)) {
                throw new \Exception('Invalid location IDs: ' . implode(', ', $missingLocations));
            }

            // Validate that auditors exist
            $auditorIds = $args['auditors'];
            $existingAuditors = $this->employeeRepository->whereIn('id', $auditorIds)->pluck('id')->toArray();
            $missingAuditors = array_diff($auditorIds, $existingAuditors);
            if (!empty($missingAuditors)) {
                throw new \Exception('Invalid auditor IDs: ' . implode(', ', $missingAuditors));
            }

            // Get or create a default user for testing
            $userId = Auth::id();
            if (!$userId) {
                // For testing purposes, get the first user or create one
                $user = \App\Models\User::first();
                if (!$user) {
                    throw new \Exception('No users found in the system. Please run the database seeder first.');
                }
                $userId = $user->id;
            }

            // Create the audit plan
            $auditPlan = $this->auditPlanRepository->create([
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
                    $notificationsSent = $this->auditNotificationService->sendInitialNotifications(
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
            }

            // Get all assets from the selected locations
            $locationNames = $this->locationRepository->getNamesByIds($args['locations']);

            if (empty($locationNames)) {
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
                $originalUserName = null;
                $currentUserName = null;

                if ($asset->user_id && $asset->employee) {
                    $originalUserName = $asset->employee->name;
                    $currentUserName = $asset->employee->name;
                }

                $auditAssets[] = [
                    'audit_plan_id' => $auditPlan->id,
                    'asset_id' => $asset->id,
                    'original_location' => $asset->location,
                    'original_user' => $originalUserName,
                    'current_location' => $asset->location,
                    'current_user' => $currentUserName,
                    'current_status' => $asset->status,
                    'audit_status' => false,
                    'resolved' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($auditAssets)) {
                AuditAsset::insert($auditAssets);

                Log::info('Audit assets created successfully', [
                    'audit_plan_id' => $auditPlan->id,
                    'total_assets' => count($auditAssets)
                ]);
            }

            // Log the creation
            AuditLog::log(
                $auditPlan->id,
                'Created',
                $userId,
                null,
                null,
                ['name' => $auditPlan->name, 'locations' => $locationNames],
                "Audit plan '{$auditPlan->name}' created with " . count($assets) . " assets"
            );

            return $auditPlan->load(['assignments.location', 'assignments.auditor', 'auditAssets.asset']);
        });
    }

    /**
     * Update an existing audit plan.
     */
    public function updateAuditPlan($rootValue, array $args)
    {
        return $this->databaseManager->transaction(function () use ($args) {
            $auditPlan = $this->auditPlanRepository->findOrFail($args['id']);

            // Store old values for logging
            $oldValues = $auditPlan->only(['name', 'start_date', 'due_date', 'status', 'description']);

            // Update the audit plan
            $updateData = array_filter($args, function ($key) {
                return in_array($key, ['name', 'start_date', 'due_date', 'status', 'description']);
            }, ARRAY_FILTER_USE_KEY);

            $auditPlan->update($updateData);

            // Log the update
            AuditLog::log(
                $auditPlan->id,
                'Updated',
                Auth::id(),
                null,
                $oldValues,
                $auditPlan->only(['name', 'start_date', 'due_date', 'status', 'description']),
                "Audit plan '{$auditPlan->name}' updated"
            );

            return $auditPlan->fresh();
        });
    }
}