<div class="overflow-x-auto scrollbar-thin">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Action
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Description
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Détails
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Utilisateur
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Date & Heure
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($logs as $log)
                @php
                    // Couleurs selon le type d'action
                    $colors = [
                        'create' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'icon' => 'fa-plus'],
                        'update' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'fa-edit'],
                        'delete' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'icon' => 'fa-trash'],
                        'mapping_create' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'icon' => 'fa-link'],
                        'mapping_update' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-800', 'icon' => 'fa-sync'],
                        'mapping_dragdrop' => ['bg' => 'bg-pink-100', 'text' => 'text-pink-800', 'icon' => 'fa-arrows-alt-h'],
                        'validate' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'icon' => 'fa-check-circle'],
                    ];
                    
                    $color = $colors[$log->action] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'icon' => 'fa-history'];
                    
                    // Obtenir les informations spécifiques selon le type
                    $details = '';
                    if ($log->model === 'AccountMapping') {
                        $oldInfo = $log->old_account_info ?? 'Compte inconnu';
                        $newInfo = $log->new_account_info ?? 'Compte inconnu';
                        $details = "De: {$oldInfo}<br>Vers: {$newInfo}";
                    } elseif ($log->model === 'OldAccount' || $log->model === 'NewAccount') {
                        $accountInfo = $log->account_info ?? 'Compte inconnu';
                        $details = $accountInfo;
                    } elseif ($log->model === 'GrandLivre') {
                        $details = "ID: {$log->model_id}";
                    }
                @endphp
                
                <tr class="table-row-hover hover:bg-gray-50 transition-colors duration-150" 
                    onclick="showDetails({{ $log->id }})">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-lg {{ $color['bg'] }} {{ $color['text'] }} 
                                  flex items-center justify-center mr-3">
                                <i class="fas {{ $color['icon'] }}"></i>
                            </div>
                            <div>
                                <div class="text-sm font-medium {{ $color['text'] }}">
                                    {{ $log->formatted_action }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $log->model }}
                                </div>
                            </div>
                        </div>
                    </td>
                    
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900 font-medium">
                            {{ $log->detailed_description }}
                        </div>
                        @if($log->changes_summary)
                            <div class="text-xs text-gray-600 mt-1">
                                <i class="fas fa-pencil-alt mr-1"></i>
                                {{ $log->changes_summary }}
                            </div>
                        @endif
                    </td>
                    
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-700">
                            {!! $details !!}
                        </div>
                        @if($log->model_id)
                            <div class="text-xs text-gray-500 mt-1">
                                ID: {{ $log->model_id }}
                            </div>
                        @endif
                    </td>
                    
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mr-3">
                                <i class="fas fa-user text-gray-600"></i>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $log->user->name ?? 'Système' }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $log->user->email ?? '' }}
                                </div>
                            </div>
                        </div>
                    </td>
                    
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                            {{ $log->created_at->format('d/m/Y') }}
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ $log->created_at->format('H:i:s') }}
                        </div>
                        <div class="text-xs text-gray-400">
                            {{ $log->created_at->diffForHumans() }}
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center">
                        <div class="text-gray-400">
                            <i class="fas fa-inbox text-4xl mb-4"></i>
                            <p class="text-lg font-medium mb-2">Aucune action trouvée</p>
                            <p class="text-sm">Essayez de modifier vos filtres</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>