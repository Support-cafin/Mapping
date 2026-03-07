<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Entreprise;
use App\Models\AccountMapping;
use App\Models\OldAccount;
use App\Models\NewAccount;
use App\Models\GrandLivre;
use Illuminate\Http\Request;
use App\Exports\AuditLogsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    /**
     * Afficher la page des logs
     */
    public function index(Request $request)
    {
        $entreprise = Auth::user()->entreprise;
        
        // Filtres par défaut (7 derniers jours)
        $filters = [
            'date_start' => $request->input('date_start', now()->subDays(7)->format('Y-m-d')),
            'date_end' => $request->input('date_end', now()->format('Y-m-d')),
            'model' => $request->input('model'),
            'action' => $request->input('action'),
            'user_id' => $request->input('user_id'),
            'search' => $request->input('search'),
        ];
        
        // Requête de base avec chargement intelligent des relations
        $query = $this->buildQueryWithRelations($entreprise->id);
        
        // Appliquer les filtres
        $this->applyFilters($query, $filters);
        
        // Pagination (50 par page)
        $logs = $query->paginate(50)->withQueryString();
        
        // Charger les relations spécifiques pour chaque log
        $this->loadSpecificRelations($logs);
        
        // Statistiques
        $stats = $this->getStats($entreprise->id, $filters);
        
        // Options pour les filtres
        $models = AuditLog::where('entreprise_id', $entreprise->id)
            ->distinct('model')
            ->pluck('model');
            
        $actions = AuditLog::where('entreprise_id', $entreprise->id)
            ->distinct('action')
            ->pluck('action');
            
        $users = $entreprise->users()->select('id', 'name')->get();
        
        return view('audit-logs.index', compact(
            'logs', 
            'stats', 
            'filters', 
            'models', 
            'actions', 
            'users',
            'entreprise'
        ));
    }
    
    /**
     * Construire la requête avec les relations appropriées
     */
    private function buildQueryWithRelations(int $entrepriseId)
    {
        return AuditLog::with(['user', 'entreprise'])
            ->where('entreprise_id', $entrepriseId)
            ->orderBy('created_at', 'desc');
    }
    
    /**
     * Charger les relations spécifiques selon le type de modèle
     */
    private function loadSpecificRelations($logs)
    {
        // Grouper les logs par type de modèle pour optimiser les requêtes
        $groupedLogs = [
            'AccountMapping' => [],
            'OldAccount' => [],
            'NewAccount' => [],
            'GrandLivre' => [],
        ];
        
        foreach ($logs as $log) {
            if (isset($groupedLogs[$log->model])) {
                $groupedLogs[$log->model][] = $log->model_id;
            }
        }
        
        // Charger les AccountMappings avec leurs relations
        if (!empty($groupedLogs['AccountMapping'])) {
            $mappings = AccountMapping::with(['oldAccount', 'newAccount'])
                ->whereIn('id', $groupedLogs['AccountMapping'])
                ->get()
                ->keyBy('id');
            
            foreach ($logs as $log) {
                if ($log->model === 'AccountMapping' && isset($mappings[$log->model_id])) {
                    $log->setRelation('accountMapping', $mappings[$log->model_id]);
                }
            }
        }
        
        // Charger les OldAccounts
        if (!empty($groupedLogs['OldAccount'])) {
            $oldAccounts = OldAccount::whereIn('id', $groupedLogs['OldAccount'])
                ->get()
                ->keyBy('id');
            
            foreach ($logs as $log) {
                if ($log->model === 'OldAccount' && isset($oldAccounts[$log->model_id])) {
                    $log->setRelation('oldAccount', $oldAccounts[$log->model_id]);
                }
            }
        }
        
        // Charger les NewAccounts
        if (!empty($groupedLogs['NewAccount'])) {
            $newAccounts = NewAccount::whereIn('id', $groupedLogs['NewAccount'])
                ->get()
                ->keyBy('id');
            
            foreach ($logs as $log) {
                if ($log->model === 'NewAccount' && isset($newAccounts[$log->model_id])) {
                    $log->setRelation('newAccount', $newAccounts[$log->model_id]);
                }
            }
        }
        
        // Charger les GrandLivre
        if (!empty($groupedLogs['GrandLivre'])) {
            $grandLivres = GrandLivre::whereIn('id', $groupedLogs['GrandLivre'])
                ->get()
                ->keyBy('id');
            
            foreach ($logs as $log) {
                if ($log->model === 'GrandLivre' && isset($grandLivres[$log->model_id])) {
                    $log->setRelation('grandLivre', $grandLivres[$log->model_id]);
                }
            }
        }
        
        return $logs;
    }
    
    /**
     * Filtrer les logs (AJAX)
     */
    public function filter(Request $request)
    {
        $entreprise = Auth::user()->entreprise;
        
        $filters = [
            'date_start' => $request->input('date_start'),
            'date_end' => $request->input('date_end'),
            'model' => $request->input('model'),
            'action' => $request->input('action'),
            'user_id' => $request->input('user_id'),
            'search' => $request->input('search'),
        ];
        
        $query = $this->buildQueryWithRelations($entreprise->id);
        
        $this->applyFilters($query, $filters);
        
        $logs = $query->paginate(50)->withQueryString();
        
        // Charger les relations spécifiques
        $this->loadSpecificRelations($logs);
        
        $stats = $this->getStats($entreprise->id, $filters);
        
        return response()->json([
            'html' => view('audit-logs.partials.logs-table', compact('logs'))->render(),
            'stats' => $stats,
            'count' => $logs->total()
        ]);
    }
    
    /**
     * Afficher les détails d'un log
     */
    public function show(AuditLog $auditLog)
    {
        // Charger toutes les relations nécessaires
        $auditLog->load(['user', 'entreprise']);
        
        // Charger la relation spécifique selon le modèle
        switch ($auditLog->model) {
            case 'AccountMapping':
                $auditLog->load(['accountMapping' => function($q) {
                    $q->with(['oldAccount', 'newAccount']);
                }]);
                break;
            case 'OldAccount':
                $auditLog->load('oldAccount');
                break;
            case 'NewAccount':
                $auditLog->load('newAccount');
                break;
            case 'GrandLivre':
                $auditLog->load('grandLivre');
                break;
        }
        
        return view('audit-logs.show', compact('auditLog'));
    }
    
    /**
     * Exporter les logs
     */
    public function export(Request $request)
    {
        $entreprise = Auth::user()->entreprise;
        $filters = $request->all();
        
        return Excel::download(
            new AuditLogsExport($entreprise->id, $filters), 
            'audit-logs-' . now()->format('Y-m-d-H-i') . '.xlsx'
        );
    }
    
    /**
     * Appliquer les filtres à la requête
     */
    private function applyFilters($query, array $filters)
    {
        // Date range
        if (!empty($filters['date_start']) && !empty($filters['date_end'])) {
            $query->whereBetween('created_at', [
                $filters['date_start'] . ' 00:00:00',
                $filters['date_end'] . ' 23:59:59'
            ]);
        }
        
        // Modèle
        if (!empty($filters['model'])) {
            $query->where('model', $filters['model']);
        }
        
        // Action
        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }
        
        // Utilisateur
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        
        // Recherche globale
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                ->orWhere('model', 'like', "%{$search}%")
                ->orWhere('action', 'like', "%{$search}%")
                ->orWhere(function($q) use ($search) {
                    $q->whereJsonContains('old_values', ['code' => $search])
                      ->orWhereJsonContains('old_values', ['intitule' => $search])
                      ->orWhereJsonContains('new_values', ['code' => $search])
                      ->orWhereJsonContains('new_values', ['intitule' => $search]);
                });
            });
        }
    }
    
    /**
     * Obtenir les statistiques
     */
    private function getStats(int $entrepriseId, array $filters): array
    {
        $query = AuditLog::where('entreprise_id', $entrepriseId);
        $this->applyFilters($query, $filters);
        
        $total = $query->count();
        $today = AuditLog::where('entreprise_id', $entrepriseId)
            ->whereDate('created_at', today())
            ->count();
            
        $byModel = AuditLog::where('entreprise_id', $entrepriseId)
            ->selectRaw('model, COUNT(*) as count')
            ->groupBy('model')
            ->orderBy('count', 'desc')
            ->take(5)
            ->pluck('count', 'model')
            ->toArray();
            
        $byAction = AuditLog::where('entreprise_id', $entrepriseId)
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->pluck('count', 'action')
            ->toArray();
            
        $byUser = AuditLog::with('user')
            ->where('entreprise_id', $entrepriseId)
            ->selectRaw('user_id, COUNT(*) as count')
            ->groupBy('user_id')
            ->orderBy('count', 'desc')
            ->take(5)
            ->get()
            ->mapWithKeys(function($item) {
                return [$item->user->name ?? 'Inconnu' => $item->count];
            })
            ->toArray();
            
        return [
            'total' => $total,
            'today' => $today,
            'by_model' => $byModel,
            'by_action' => $byAction,
            'by_user' => $byUser,
        ];
    }
}