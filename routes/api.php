<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\DonateurController;
use App\Http\Controllers\Api\BalanceController;
use App\Http\Controllers\Api\SwaggerController;
use App\Http\Controllers\Api\GrandLivreExportController;
use App\Http\Controllers\Api\GrandLivreExportPdfController;
use App\Http\Controllers\Api\GrandLivrePdfController;
use App\Http\Controllers\Api\GrandLivreGeneralExportController;
use App\Http\Controllers\Api\ProfileApiController;
use App\Http\Controllers\Api\ExerciceController;
use App\Http\Controllers\Api\AccountMappingController;
use App\Http\Controllers\Api\GrandLivreController;
use App\Services\LoginSecurityService;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('auth/login',  [SwaggerController::class, 'login']);
Route::post('auth/logout', [SwaggerController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {

    // ── Admin ─────────────────────────────────────────
    Route::prefix('admin')->group(function () {
        Route::get('dashboard',                    [AdminController::class, 'dashboard']);
        Route::get('entreprises',                  [AdminController::class, 'entreprises']);
        Route::post('entreprises',                 [AdminController::class, 'storeEntreprise']);
        Route::get('entreprises/{entreprise}',     [AdminController::class, 'showEntreprise']);
        Route::put('entreprises/{entreprise}',     [AdminController::class, 'updateEntreprise']);
        Route::delete('entreprises/{entreprise}',  [AdminController::class, 'destroyEntreprise']);
        Route::get('users',                        [AdminController::class, 'users']);
        Route::post('users',                       [AdminController::class, 'storeUser']);
        Route::put('users/{id}',                   [AdminController::class, 'updateUser']);
        Route::delete('users/{id}',                [AdminController::class, 'destroyUser']);
    });

    // ── Audit Logs ────────────────────────────────────
    Route::prefix('audit-logs')->group(function () {
        Route::get('/',              [AuditLogController::class, 'index']);
        Route::get('filter',         [AuditLogController::class, 'filter']);
        Route::get('{auditLog}',     [AuditLogController::class, 'show']);
        Route::get('export/excel',   [AuditLogController::class, 'export']);
    });

    // ── Donateurs (exports anciens supprimés — gérés par DonateurController ci-dessous)

    // ── Grand Livre (exports anciens) ─────────────────
    Route::prefix('grand-livre')->group(function () {
        Route::get('template',                  [GrandLivreExportController::class, 'template']);
        Route::get('export/{format}',           [GrandLivreExportController::class, 'export']);
        Route::get('export/pdf/detail',         [GrandLivreExportPdfController::class, 'exportPdf']);
        Route::get('export/pdf/general',        [GrandLivreExportPdfController::class, 'exportPdfGeneral']);
        Route::get('print',                     [GrandLivreExportPdfController::class, 'print']);
        Route::get('general/export/pdf',        [GrandLivreGeneralExportController::class, 'pdf']);
        Route::get('general/export/excel',      [GrandLivreGeneralExportController::class, 'excel']);
        Route::get('{entrepriseId}/export/pdf', [GrandLivrePdfController::class, 'export']);
    });

    // ── Profile (BLOC UNIQUE via ProfileApiController) ───
    Route::prefix('profile')->group(function () {
        Route::get('/',       [ProfileApiController::class, 'show']);
        Route::put('/',       [ProfileApiController::class, 'update']);
        Route::put('password',[ProfileApiController::class, 'updatePassword']);
        Route::delete('/',    [ProfileApiController::class, 'destroy']);
    });

    // ── Exercices ─────────────────────────────────────
    Route::prefix('exercices')->group(function () {
        Route::get('/',              [ExerciceController::class, 'index']);
        Route::post('/',             [ExerciceController::class, 'store']);
        Route::get('/{id}',          [ExerciceController::class, 'show']);
        Route::put('/{id}',          [ExerciceController::class, 'update']);
        Route::delete('/{id}',       [ExerciceController::class, 'destroy']);
        Route::patch('/{id}/statut', [ExerciceController::class, 'toggleStatut']);
    });

    // ── Plan Comptable ────────────────────────────────
    Route::prefix('plan-comptable')->group(function () {
        Route::get('old-accounts',      [AccountMappingController::class, 'oldAccountsTree']);
        Route::get('new-accounts',      [AccountMappingController::class, 'newAccountsTree']);
        Route::get('mappings',          [AccountMappingController::class, 'index']);
        Route::post('mappings',         [AccountMappingController::class, 'store']);
        Route::get('mappings/{id}',     [AccountMappingController::class, 'show']);
        Route::put('mappings/{id}',     [AccountMappingController::class, 'update']);
        Route::delete('mappings/{id}',  [AccountMappingController::class, 'destroy']);
        Route::delete('mappings',       [AccountMappingController::class, 'destroyAll']);
        Route::post('mappings/sync',    [AccountMappingController::class, 'syncAll']);
        Route::get('mappings/template', [AccountMappingController::class, 'downloadTemplate']);
        Route::post('mappings/import',  [AccountMappingController::class, 'import']);
    });

    // ── Grand Livre (API principale) ──────────────────
    // ⚠️ ORDRE CRITIQUE : routes spécifiques AVANT routes avec paramètres
    Route::prefix('grand-livre')->group(function () {
        Route::get('general/export',         [GrandLivreController::class, 'generalExportExcel']);
        Route::get('general',                [GrandLivreController::class, 'general']);
        Route::get('stats',                  [GrandLivreController::class, 'stats']);
        Route::get('journaux',               [GrandLivreController::class, 'journaux']);
        Route::get('comptes',                [GrandLivreController::class, 'comptes']);
        Route::post('sync',                  [GrandLivreController::class, 'syncMappings']);
        Route::post('import',                [GrandLivreController::class, 'import']);
        Route::delete('ecritures/bulk',      [GrandLivreController::class, 'destroyMultiple']);
        Route::delete('ecritures/all',       [GrandLivreController::class, 'destroyAll']);
        Route::get('ecritures',              [GrandLivreController::class, 'index']);
        Route::post('ecritures',             [GrandLivreController::class, 'store']);
        Route::get('ecritures/{id}',         [GrandLivreController::class, 'show']);
        Route::put('ecritures/{id}',         [GrandLivreController::class, 'update']);
        Route::delete('ecritures/{id}',      [GrandLivreController::class, 'destroy']);
        Route::post('ecritures/{id}/lettre', [GrandLivreController::class, 'lettre']);
    });

    // ── Balance (API — BLOC UNIQUE via BalanceController) ─────────────────────
    // ⚠️ ORDRE CRITIQUE : spécifiques AVANT paramètres
    // ⚠️ Un seul Route::prefix('balance') — évite tout conflit de routage
    Route::prefix('balance')->group(function () {

        // ── Données ──────────────────────────────────
        Route::get('stats',               [BalanceController::class, 'stats']);
        Route::get('4colonnes',           [BalanceController::class, 'balance4Colonnes']);
        Route::get('6colonnes',           [BalanceController::class, 'balance6Colonnes']);
        Route::get('compte/{id}/details', [BalanceController::class, 'detailCompte']);

        // ── Tiers — routes spécifiques AVANT tiers/{parametre} ───────────
        Route::get('tiers/export',               [BalanceController::class, 'exportTiers']);
        Route::get('tiers/compte/{id}/details',  [BalanceController::class, 'detailTiers']);
        Route::get('tiers',                      [BalanceController::class, 'balanceTiers']);

        // ── Exports — spécifiques AVANT génériques ────────────────────────
        Route::get('export/typed',  [BalanceController::class, 'exportTyped']);
        Route::get('export/excel',  [BalanceController::class, 'exportExcel']);
        Route::get('export',        [BalanceController::class, 'export']);
    });

    // ── Donateurs (API — BLOC UNIQUE via DonateurApiController) ──────────────
    // ⚠️ ORDRE CRITIQUE : routes spécifiques AVANT routes avec paramètres
    Route::prefix('donateurs')->group(function () {
        // Fixes sans paramètre — AVANT /{id}
        Route::get('filtres',      [DonateurController::class, 'filtres']);
        Route::get('stats',        [DonateurController::class, 'stats']);
        Route::get('export/pdf',   [DonateurController::class, 'exportPdf']);

        // CRUD
        Route::get('/',            [DonateurController::class, 'index']);
        Route::post('/',           [DonateurController::class, 'store']);
        Route::get('/{id}',        [DonateurController::class, 'show']);
        Route::put('/{id}',        [DonateurController::class, 'update']);
        Route::delete('/{id}',     [DonateurController::class, 'destroy']);
        Route::patch('/{id}/statut',   [DonateurController::class, 'updateStatut']);
        Route::get('/{id}/fiche/pdf',  [DonateurController::class, 'fichePdf']);
    });

});
