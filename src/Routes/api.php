<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Bu\DAL\Database\Repositories\LocationRepository;
use Bu\DAL\Database\Repositories\AssetRepository;
use Bu\DAL\Database\Repositories\EmployeeRepository;
use Bu\DAL\Database\Repositories\ProjectRepository;
use Bu\DAL\Database\Repositories\AuditPlanRepository;
use Bu\DAL\Database\Repositories\AuditAssetRepository;
use Bu\DAL\Database\Repositories\AuditAssignmentRepository;
use Bu\DAL\Database\Repositories\CorrectiveActionRepository;

/*
|--------------------------------------------------------------------------
| Package API Routes
|--------------------------------------------------------------------------
|
| These routes are automatically loaded by the DALServiceProvider.
| They provide REST API endpoints for all package models.
|
*/

Route::prefix('api')->group(function () {
    // Locations API
    Route::get('/locations', function (Request $request) {
        $repository = app(LocationRepository::class);
        $locations = $repository->all();
        return response()->json($locations);
    });

    // Assets API
    Route::get('/assets', function (Request $request) {
        $repository = app(AssetRepository::class);
        $assets = $repository->all();
        return response()->json($assets);
    });

    // Employees API
    Route::get('/employees', function (Request $request) {
        $repository = app(EmployeeRepository::class);
        $employees = $repository->all();
        return response()->json($employees);
    });

    // Projects API
    Route::get('/projects', function (Request $request) {
        $repository = app(ProjectRepository::class);
        $projects = $repository->all();
        return response()->json($projects);
    });

    // Audit Plans API
    Route::get('/audit-plans', function (Request $request) {
        $repository = app(AuditPlanRepository::class);
        $auditPlans = $repository->all();
        return response()->json($auditPlans);
    });

    // Audit Assets API
    Route::get('/audit-assets', function (Request $request) {
        $repository = app(AuditAssetRepository::class);
        $auditAssets = $repository->all();
        return response()->json($auditAssets);
    });

    // Audit Assignments API
    Route::get('/audit-assignments', function (Request $request) {
        $repository = app(AuditAssignmentRepository::class);
        $auditAssignments = $repository->all();
        return response()->json($auditAssignments);
    });

    // Corrective Actions API
    Route::get('/corrective-actions', function (Request $request) {
        $repository = app(CorrectiveActionRepository::class);
        $correctiveActions = $repository->all();
        return response()->json($correctiveActions);
    });
});