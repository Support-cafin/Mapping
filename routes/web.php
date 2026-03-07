<?php

use App\Http\Controllers\GrandLivreExportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\DonateurController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use \App\Livewire\GrandLivre;
use App\Livewire\GrandLivre\Recap;
use App\Exports\GrandLivreExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GrandLivreGeneralExport;
use Illuminate\Http\Request;
use App\Livewire\Donateurs\ListeDonateurs;
use App\Livewire\Donateurs\FormulaireDonateur;
use App\Livewire\Donateurs\FicheDonateur;
use App\Http\Controllers\DonateurPdfController;
use App\Http\Controllers\GrandLivreGeneralExportController;
use App\Http\Controllers\GrandLivrePdfController;
use App\Livewire\Financial\Bilan;
use App\Livewire\Financial\CompteDeResultat;
use App\Livewire\Financial\FluxTresorerie;
use App\Services\LoginSecurityService;
use App\Livewire\Parametre\ExerciceManager;
use App\Http\Controllers\GrandLivreExportPdfController;
use App\Livewire\Balance\TiersBalance;



Route::middleware(['auth'])->group(function () {
    // Export PDF détaillé
    Route::get('/grand-livre/export-pdf1', [GrandLivreExportPdfController::class, 'exportPdf'])
        ->name('grand-livre.export-pdf1');
    
    // Export PDF général
    Route::get('/grand-livre/general/export-pdf1', [GrandLivreExportPdfController::class, 'exportPdfGeneral'])
        ->name('grand-livre.general.export-pdf1');
    
    // Impression directe
    Route::get('/grand-livre/print1', [GrandLivreExportPdfController::class, 'print'])
        ->name('grand-livre.print1');
});
Route::middleware(['auth'])->group(function () {
    Route::get('/parametres/exercices', ExerciceManager::class)
         ->name('parametres.exercices');
});

// ... vos autres routes ...

// Route pour la vérification de sécurité (accessible via AJAX)
Route::post('/login-security-status', function (Illuminate\Http\Request $request) {
    $service = new LoginSecurityService();
    $email = $request->input('email');
    $ip = $request->ip();
    
    if (!$email) {
        return response()->json([
            'requiresCaptcha' => false,
            'attempts' => 0,
            'isBlocked' => false,
            'blockTime' => 0
        ]);
    }
    
    $attempts = $service->getFailedAttempts($email, $ip);
    
    return response()->json([
        'requiresCaptcha' => $attempts >= 2,
        'attempts' => $attempts,
        'isBlocked' => $service->isBlocked($email, $ip),
        'blockTime' => $service->getBlockTimeRemaining($email, $ip)
    ]);
})->middleware('web');
Route::get('/', function () {
    // Si déjà connecté → dashboard
    if (auth()->check()) {
        return redirect()->route('parametres.exercices');
    }

    // Sinon → page login Livewire Volt
    return redirect()->route('login');
});

// routes/web.php (temporaire)
Route::get('/debug-security/{email}', function($email) {
    $service = new \App\Services\LoginSecurityService();
    $ip = request()->ip();
    
    $data = [
        'requiresCaptcha' => $service->requiresCaptcha($email, $ip),
        'attempts' => $service->getFailedAttempts($email, $ip),
        'isBlocked' => $service->isBlocked($email, $ip),
        'blockTime' => $service->getBlockTimeRemaining($email, $ip)
    ];
    
    return response()->json($data);
});

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboard/exxcel', function () {
     return view('excel.editor');
 });

Route::get('//entreprise/mapping/dual-panel', \App\Livewire\Mapping\DualPanel::class)
    ->middleware(['auth', 'verified'])->name('mapping.dual_panel');
    

// Routes des états financiers
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/bilan', \App\Livewire\Financial\Bilan::class)->name('bilan');
    Route::get('/compte-resultat', \App\Livewire\Financial\CompteDeResultat::class)->name('compte.resultat');
    Route::get('/flux-tresorerie', \App\Livewire\Financial\FluxTresorerie::class)->name('flux.tresorerie');
    
    // Export
    Route::get('/bilan/export', [\App\Livewire\Financial\Bilan::class, 'export'])->name('bilan.export');
    Route::get('/compte-resultat/export', [\App\Livewire\Financial\CompteDeResultat::class, 'export'])->name('compte.resultat.export');
    Route::get('/flux-tresorerie/export', [\App\Livewire\Financial\FluxTresorerie::class, 'export'])->name('flux.tresorerie.export');
});

Route::middleware('auth')->group(function () {
    
    // routes/web.php
    Route::get('/grand-livre-general/export-pdf/{entreprise}', [GrandLivrePdfController::class, 'export'])
     ->name('grand-livre-general.pdf.export')
     ->middleware('auth');
    // PROFILE ROUTES
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // routes/web.php
    Route::get('/donateurs/{donateur}/fiche-pdf', [DonateurController::class, 'fichePdf'])
     ->name('donateurs.fiche.pdf');
     
    Route::get('/grand-livre/export-pdf/{token}', [GrandLivreGeneralExportController::class, 'pdf'])
    ->name('grand-livre.export-pdf');

    Route::get('/grand-livre/export-excel', [GrandLivreGeneralExportController::class, 'excel'])
    ->name('grand-livre.export-excel');
    
    // Route d'export simple
    Route::get('/grand-livre/export-general-simple', function (Request $request) {
        $filters = $request->all();
        
        if (empty($filters['entreprise_id'])) {
            abort(403, 'Entreprise non spécifiée');
        }
        
        // Valider que l'utilisateur a accès à cette entreprise
        $entreprise = \App\Models\Entreprise::findOrFail($filters['entreprise_id']);
        
        if (auth()->user()->entreprise_id != $entreprise->id) {
            abort(403, 'Accès non autorisé');
        }
        
        $export = new GrandLivreGeneralExport(
            $entreprise->id,
            $filters
        );
        
        $fileName = 'grand_livre_general_' . $entreprise->code . '_' . date('Ymd_His') . '.xlsx';
        
        return \Maatwebsite\Excel\Facades\Excel::download(
            $export,
            $fileName
        );
    })->name('grand-livre.export-general-simple')->middleware('auth');
    // Dans web.php
Route::get('/grand-livre/export-stream', function (Request $request) {
    $filters = $request->all();
    
    if (empty($filters['entreprise_id'])) {
        abort(403, 'Entreprise non spécifiée');
    }
    
    // Vérifier les permissions
    $user = auth()->user();
    if ($user->entreprise_id != $filters['entreprise_id']) {
        abort(403, 'Accès non autorisé');
    }
    
    // Augmenter les limites
    ini_set('memory_limit', '-1');
    set_time_limit(0);
    
    $export = new \App\Exports\GrandLivreStreamExport(
        $filters['entreprise_id'],
        $filters
    );
    
    $fileName = 'grand_livre_' . date('Ymd_His') . '.xlsx';
    
    return \Maatwebsite\Excel\Facades\Excel::download(
        $export,
        $fileName
    );
})->name('grand-livre.export-stream')->middleware('auth');

    // MAPPING ROUTES
    Route::get('/entreprise/dashboard', \App\Livewire\Enterprise\Dashboard::class)
        ->name('entreprise.dashboard');

    Route::get('/entreprise/mapping/dual-panel', \App\Livewire\Mapping\DualPanel::class)
        ->name('mapping.dual_panel');
        
    Route::get('/plan-comptable', \App\Livewire\PlanComptable\DualPanel::class)->name('plan.index');
    
    // Route::get('/grand-livre', \App\Livewire\GrandLivre\Index::class)->name('grand-livre.index');
    Route::get('/grand-livre', \App\Livewire\GrandLivre\Index::class)->name('grand-livre.detail');
    Route::get('/grand-livre-general', \App\Livewire\GrandLivre\General::class)->name('grand-livre.general');
    
    
    // Route pour le récapitulatif du Grand Livre
    Route::get('/grand-livre-recap', Recap::class)->name('grand-livre.recap');
    
    Route::get('/balances', \App\Livewire\Balances\ManageBalances::class)->name('balances.manage');
        
    Route::get('/entreprise/mapping', \App\Livewire\Mapping\Table::class)->name('mapping.table');
    
    Route::get('/balance/tiers', \App\Livewire\Balance\TiersBalance::class)->name('balance.tiers');

    Route::get('/balance/index', [BalanceController::class, 'index'])->name('balances.index');
    Route::get('/balance/export', [BalanceController::class, 'export'])->name('balances.export');
    Route::post('/balance/generate', [BalanceController::class, 'generate'])->name('balances.generate');
    
    
    Route::prefix('donateurs')->name('donateurs.')->group(function () {
        Route::get('/', ListeDonateurs::class)->name('index');
        Route::get('/create', FormulaireDonateur::class)->name('create');
        //Route::get('/{donateur}/edit', FormulaireDonateur::class)->name('edit');
        Route::get('/{donateur}', FicheDonateur::class)->name('show');
        Route::get('/{donateur}/edit', \App\Livewire\Donateurs\EditDonateur::class)->name('edit');
        
        // Export routes
        Route::get('/export/excel', [ListeDonateurs::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [ListeDonateur::class, 'exportPdf'])->name('export.pdf');
    });
    
    Route::get('/donateurs/export/pdf', [DonateurPdfController::class, 'export'])
     ->name('donateurs.export.pdf')
     ->middleware('auth');

    Route::middleware(['auth'])->prefix('admin')->group(function () {
        Route::get('/entreprises', [AdminController::class, 'entreprises'])->name('admin.entreprises.index');
        Route::get('/entreprises/create', [AdminController::class, 'createEntreprise'])->name('admin.entreprises.create');
        Route::post('/entreprises', [AdminController::class, 'storeEntreprise'])->name('admin.entreprises.store');
        Route::get('/entreprises/{entreprise}', [AdminController::class, 'showEntreprise'])->name('admin.entreprises.show');
        Route::get('/entreprises/{entreprise}/edit', [AdminController::class, 'editEntreprise'])->name('admin.entreprises.edit');
        Route::put('/entreprises/{entreprise}', [AdminController::class, 'updateEntreprise'])->name('admin.entreprises.update');
        Route::delete('/entreprises/{entreprise}', [AdminController::class, 'destroyEntreprise'])->name('admin.entreprises.destroy');
        
        
        // Routes pour la gestion des utilisateurs
        Route::get('/users', [AdminController::class, 'users'])->name('admin.users.index');
        Route::get('/users/create', [AdminController::class, 'createUser'])->name('admin.users.create');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
    });

    
    // Export
    // Route::get('/grand-livre/export/{format}', [GrandLivreExportController::class, 'export'])
    //      ->name('grand-livre.export');
    
    Route::get('/grand-livre/template', [GrandLivreExportController::class, 'template'])
     ->name('grand-livre.template');
     
     
    Route::get('/grand-livre/export', function (Request $request) {
        $entreprise = auth()->user()->entreprise;
        $filters = $request->input('filters', []);
        $tab = $request->input('tab', 'detail');
        
        $filename = $tab === 'recap' 
            ? 'grand_livre_general_' . date('Ymd_His') . '.xlsx'
            : 'grand_livre_detail_' . date('Ymd_His') . '.xlsx';
        
        return Excel::download(
            new GrandLivreExport($entreprise, $filters, $tab),
            $filename
        );
    })->name('grand-livre.export');
    
    
    // NEW
    Route::get('/grand-livre/export-general', function (Request $request) {
        $entreprise = auth()->user()->entreprise;
        $filters = $request->input('filters', []);
        
        $filename = 'grand_livre_general_' . $entreprise->code . '_' . date('Ymd_His') . '.xlsx';
        
        return Excel::download(
            new GrandLivreGeneralExport($entreprise, $filters),
            $filename
        );
    })->name('grand-livre.export-general');
    
    Route::get('/grand-livre/export-detail', function (Request $request) {
        $entreprise = auth()->user()->entreprise;
        $filters = $request->input('filters', []);
        
        $filename = 'grand_livre_detail_' . $entreprise->code . '_' . date('Ymd_His') . '.xlsx';
        
        return Excel::download(
            new GrandLivreExport($entreprise, $filters, 'detail'),
            $filename
        );
    })->name('grand-livre.export-detail');
    
    
    // LOGS
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/audit-logs/filter', [AuditLogController::class, 'filter'])->name('audit-logs.filter');
    Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');
    Route::post('/audit-logs/export', [AuditLogController::class, 'export'])->name('audit-logs.export');
    
    // Export balance
    Route::get('/balance/export-excel', [BalanceController::class, 'exportExcel'])
    ->name('balance.export');
     
});


require __DIR__.'/auth.php';
