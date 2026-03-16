<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /api/audit-logs
     * Paramètres : search, action, model, user_id, date_debut, date_fin, page, per_page
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $entrepriseId = Auth::user()->entreprise_id;

            $query = AuditLog::where('entreprise_id', $entrepriseId)
                ->with('user:id,name,email')
                ->orderBy('created_at', 'desc');

            // Filtres
            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(function ($q) use ($s) {
                    $q->where('description', 'like', "%{$s}%")
                        ->orWhere('action', 'like', "%{$s}%")
                        ->orWhere('model', 'like', "%{$s}%")
                        ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$s}%"));
                });
            }

            if ($request->filled('action')) $query->where('action', $request->action);
            if ($request->filled('model'))  $query->where('model', $request->model);
            if ($request->filled('user_id')) $query->where('user_id', $request->user_id);

            if ($request->filled('date_debut')) {
                $query->whereDate('created_at', '>=', $request->date_debut);
            }
            if ($request->filled('date_fin')) {
                $query->whereDate('created_at', '<=', $request->date_fin);
            }

            $perPage   = (int) $request->get('per_page', 20);
            $paginator = $query->paginate($perPage);

            // Enrichir chaque log avec les infos formatées
            $logs = collect($paginator->items())->map(fn($log) => $this->formatLog($log));

            // Valeurs distinctes pour les filtres
            $actions = AuditLog::where('entreprise_id', $entrepriseId)
                ->distinct()->pluck('action')->filter()->values();
            $models  = AuditLog::where('entreprise_id', $entrepriseId)
                ->distinct()->pluck('model')->filter()->values();

            return $this->success('OK', [
                'logs'       => $logs,
                'filters'    => ['actions' => $actions, 'models' => $models],
                'pagination' => [
                    'total'        => $paginator->total(),
                    'per_page'     => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                ],
                'stats' => [
                    'today'  => AuditLog::where('entreprise_id', $entrepriseId)->whereDate('created_at', today())->count(),
                    'week'   => AuditLog::where('entreprise_id', $entrepriseId)->where('created_at', '>=', now()->subWeek())->count(),
                    'total'  => AuditLog::where('entreprise_id', $entrepriseId)->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * GET /api/audit-logs/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $log = AuditLog::where('entreprise_id', Auth::user()->entreprise_id)
                ->with('user:id,name,email')
                ->find($id);

            if (!$log) return $this->notFound('Log introuvable.');

            return $this->success('OK', $this->formatLog($log, true));
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * GET /api/audit-logs/filter
     * Retourne les valeurs disponibles pour les filtres
     */
    public function filter(): JsonResponse
    {
        try {
            $entrepriseId = Auth::user()->entreprise_id;

            return $this->success('OK', [
                'actions' => AuditLog::where('entreprise_id', $entrepriseId)->distinct()->pluck('action')->filter()->values(),
                'models'  => AuditLog::where('entreprise_id', $entrepriseId)->distinct()->pluck('model')->filter()->values(),
                'users'   => AuditLog::where('entreprise_id', $entrepriseId)
                    ->with('user:id,name')
                    ->get()
                    ->pluck('user')
                    ->filter()
                    ->unique('id')
                    ->values(),
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper — formate un log pour l'API
    // ─────────────────────────────────────────────────────────────────────────
    private function formatLog($log, bool $withProperties = false): array
    {
        // Couleurs selon action
        $colors = [
            'create'           => ['bg' => 'bg-green-100',  'text' => 'text-green-800',  'badge' => 'green',  'icon' => '➕'],
            'update'           => ['bg' => 'bg-blue-100',   'text' => 'text-blue-800',   'badge' => 'blue',   'icon' => '✏️'],
            'delete'           => ['bg' => 'bg-red-100',    'text' => 'text-red-800',    'badge' => 'red',    'icon' => '🗑️'],
            'mapping_create'   => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'badge' => 'purple', 'icon' => '🔗'],
            'mapping_update'   => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-800', 'badge' => 'indigo', 'icon' => '🔄'],
            'mapping_dragdrop' => ['bg' => 'bg-pink-100',   'text' => 'text-pink-800',   'badge' => 'pink',   'icon' => '↔️'],
            'validate'         => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'badge' => 'yellow', 'icon' => '✅'],
        ];
        $color = $colors[$log->action] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'badge' => 'gray', 'icon' => '📋'];

        // Détails selon le modèle
        $details = '';
        if ($log->model === 'AccountMapping') {
            $oldInfo = $log->old_account_info ?? 'Compte inconnu';
            $newInfo = $log->new_account_info ?? 'Compte inconnu';
            $details = "De: {$oldInfo} → Vers: {$newInfo}";
        } elseif (in_array($log->model, ['OldAccount', 'NewAccount'])) {
            $details = $log->account_info ?? 'Compte inconnu';
        } elseif ($log->model === 'GrandLivre') {
            $details = "ID: {$log->model_id}";
        }

        $data = [
            'id'                  => $log->id,
            'action'              => $log->action,
            'formatted_action'    => $log->formatted_action ?? ucfirst(str_replace('_', ' ', $log->action)),
            'model'               => $log->model,
            'model_id'            => $log->model_id,
            'description'         => $log->description ?? $log->detailed_description ?? '',
            'changes_summary'     => $log->changes_summary ?? null,
            'details'             => $details,
            'color'               => $color,
            'user'                => $log->user ? [
                'id'    => $log->user->id,
                'name'  => $log->user->name,
                'email' => $log->user->email,
            ] : ['id' => null, 'name' => 'Système', 'email' => ''],
            'created_at'          => $log->created_at?->toISOString(),
            'created_at_formatted'=> $log->created_at?->format('d/m/Y H:i:s'),
            'created_at_human'    => $log->created_at?->diffForHumans(),
        ];

        if ($withProperties) {
            $data['old_values'] = $log->old_values ?? $log->properties['old'] ?? null;
            $data['new_values'] = $log->new_values ?? $log->properties['attributes'] ?? null;
        }

        return $data;
    }
}
