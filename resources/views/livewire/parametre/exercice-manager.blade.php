{{-- resources/views/livewire/parametre/exercice-manager.blade.php --}}
<div class="exercice-manager" x-data="{ activeTab: 'all' }">
    <!-- Style personnalisé -->
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
            transition: all 0.3s ease;
        }

        .glass-card:hover {
            box-shadow: 0 12px 48px 0 rgba(31, 38, 135, 0.2);
            transform: translateY(-2px);
        }

        .stat-card {
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            border: none;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            pointer-events: none;
        }

        .stat-icon {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 3rem;
            opacity: 0.2;
            color: white;
        }

        .btn-premium {
            padding: 0.6rem 1.5rem;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .search-premium {
            border-radius: 50px;
            padding: 0.6rem 1.2rem;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .search-premium:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
        }

        .table-premium {
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        .table-premium thead th {
            background: #f8f9fa;
            border: none;
            padding: 1rem;
            font-weight: 600;
            color: #495057;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-premium tbody tr {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            transition: all 0.3s ease;
        }

        .table-premium tbody tr:hover {
            box-shadow: 0 8px 24px rgba(67, 97, 238, 0.12);
            transform: scale(1.01);
        }

        .table-premium td {
            padding: 1.2rem 1rem;
            border: none;
            vertical-align: middle;
        }

        .table-premium td:first-child {
            border-top-left-radius: 16px;
            border-bottom-left-radius: 16px;
        }

        .table-premium td:last-child {
            border-top-right-radius: 16px;
            border-bottom-right-radius: 16px;
        }

        .badge-premium {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            border: none;
            margin: 0 2px;
        }

        .btn-action:hover {
            transform: translateY(-2px);
        }

        /* CORRECTION DU MODAL - Positionnement fixe et centré */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1050;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-y: auto;
            padding: 1rem;
        }

        .modal-premium .modal-content {
            border-radius: 24px;
            border: none;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            max-width: 800px;
            width: 100%;
            margin: auto;
        }

        .modal-premium .modal-header {
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1.5rem;
        }

        .modal-premium .modal-body {
            padding: 2rem;
        }

        .modal-premium .modal-footer {
            border-top: 1px solid rgba(0,0,0,0.05);
            padding: 1.5rem;
        }

        .form-premium {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-premium:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
            outline: none;
        }

        /* Switch toggle personnalisé */
        .form-check-input {
            background-color: #dee2e6;
            border: 2px solid #dee2e6;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-check-input:checked {
            background-color: #06d6a0;
            border-color: #06d6a0;
            box-shadow: 0 0 0 3px rgba(6, 214, 160, 0.2);
        }

        .form-check-input:focus {
            box-shadow: 0 0 0 3px rgba(6, 214, 160, 0.2);
            border-color: #06d6a0;
        }

        /* MODAL MODERNE - Design épuré et professionnel */
        .modal-container {
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
            padding: 1rem;
        }

        .modal-modern {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header */
        .modal-modern-header {
            padding: 2rem 2rem 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .modal-modern-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0 0 0.5rem;
        }

        .modal-modern-subtitle {
            font-size: 0.9rem;
            color: #6c757d;
            margin: 0;
        }

        .modal-close-btn {
            background: #f5f5f5;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            color: #666;
        }

        .modal-close-btn:hover {
            background: #e0e0e0;
            color: #333;
        }

        /* Body */
        .modal-modern-body {
            padding: 2rem;
        }

        /* Groupes de formulaire */
        .form-group-modern {
            margin-bottom: 1.5rem;
        }

        .form-label-modern {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .form-input-modern {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border: 2px solid #e5e5e5;
            border-radius: 10px;
            transition: all 0.2s;
            background: white;
        }

        .form-input-modern:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .form-input-modern.is-invalid {
            border-color: #dc3545;
        }

        .form-input-modern.is-invalid:focus {
            box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.1);
        }

        .form-error-modern {
            display: block;
            font-size: 0.8rem;
            color: #dc3545;
            margin-top: 0.5rem;
        }

        /* Conteneur de sélection de statut */
        .status-toggle-container {
            display: flex;
            gap: 1rem;
            align-items: stretch;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 12px;
            border: 2px solid #e5e5e5;
        }

        .status-option {
            flex: 1;
            padding: 1rem;
            background: white;
            border-radius: 10px;
            border: 2px solid transparent;
            transition: all 0.3s;
            cursor: pointer;
        }

        .status-option-active {
            border-color: #667eea;
            background: #f0f3ff;
        }

        .status-option-content {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .status-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .status-info {
            flex: 1;
        }

        .status-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #333;
            margin: 0 0 0.25rem;
        }

        .status-description {
            font-size: 0.8rem;
            color: #6c757d;
            margin: 0;
            line-height: 1.4;
        }

        /* Toggle switch wrapper */
        .toggle-switch-wrapper {
            display: flex;
            align-items: center;
        }

        /* Toggle switch moderne */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 56px;
            height: 32px;
        }

        .toggle-input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .3s;
            border-radius: 32px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 24px;
            width: 24px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .toggle-input:checked + .toggle-slider {
            background-color: #667eea;
        }

        .toggle-input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }

        /* Alert moderne */
        .alert-modern {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
        }

        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
        }

        .alert-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #856404;
            flex-shrink: 0;
        }

        .alert-content {
            flex: 1;
            font-size: 0.875rem;
            color: #856404;
            line-height: 1.5;
        }

        /* Footer */
        .modal-modern-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid #f0f0f0;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            background: #fafafa;
        }

        .btn-modern {
            padding: 0.75rem 2rem;
            font-size: 0.95rem;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-secondary {
            background: #e5e5e5;
            color: #333;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
        }

        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* Boutons de filtre améliorés */
        .btn-group .btn {
            position: relative;
            transition: all 0.2s ease;
        }

        .btn-group .btn:hover {
            transform: translateY(-1px);
        }

        .btn-group .btn.btn-primary {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }

        .btn-group .btn.btn-success {
            background: linear-gradient(135deg, #06d6a0 0%, #02a17a 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(6, 214, 160, 0.3);
        }

        .btn-group .btn.btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }

        .empty-state {
            padding: 4rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 24px;
            color: white;
        }

        .pagination-premium {
            gap: 0.5rem;
        }

        .pagination-premium .page-link {
            border-radius: 12px;
            border: none;
            padding: 0.5rem 1rem;
            color: #6c757d;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .pagination-premium .page-link:hover {
            background: #4361ee;
            color: white;
            transform: translateY(-2px);
        }

        .pagination-premium .active .page-link {
            background: #4361ee;
            color: white;
            box-shadow: 0 8px 16px rgba(67, 97, 238, 0.3);
        }

        .animate-slide-in {
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .sortable:hover {
            color: #4361ee;
            cursor: pointer;
        }
        
        .btn-check:checked + .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }

        .table-premium tbody tr {
            animation: slideIn 0.3s ease;
            animation-fill-mode: both;
        }

        .table-premium tbody tr:nth-child(1) { animation-delay: 0.1s; }
        .table-premium tbody tr:nth-child(2) { animation-delay: 0.2s; }
        .table-premium tbody tr:nth-child(3) { animation-delay: 0.3s; }
        .table-premium tbody tr:nth-child(4) { animation-delay: 0.4s; }
        .table-premium tbody tr:nth-child(5) { animation-delay: 0.5s; }
        .table-premium tbody tr:nth-child(6) { animation-delay: 0.6s; }
        .table-premium tbody tr:nth-child(7) { animation-delay: 0.7s; }
        .table-premium tbody tr:nth-child(8) { animation-delay: 0.8s; }
        .table-premium tbody tr:nth-child(9) { animation-delay: 0.9s; }
        .table-premium tbody tr:nth-child(10) { animation-delay: 1s; }

        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(3px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .loading-content {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
    </style>

    <!-- Header avec gradient -->
    <div class="d-flex justify-content-between align-items-center mb-5 animate-slide-in">
        <div>
            <h1 class="display-6 fw-bold" style="color: #2d3436;">
                <i class="fas fa-calendar-alt me-2" style="color: #4361ee;"></i>
                Exercices comptables
            </h1>
            <p class="text-muted mb-0">
                <i class="fas fa-chart-line me-1"></i>
                Gérez vos périodes comptables et leur statut
            </p>
        </div>
        <div>
            <button wire:click="create" class="btn btn-premium text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <i class="fas fa-plus-circle"></i>
                Nouvel exercice
            </button>
        </div>
    </div>

    {{-- FILTRES ET RECHERCHE - COMMENTÉS TEMPORAIREMENT
    <!-- Filtres et recherche - Design moderne -->
    <div class="mb-4">
        <!-- Barre de recherche -->
        <div class="glass-card rounded-4 p-3 mb-3 animate-slide-in" style="animation-delay: 0.2s;">
            <div class="position-relative">
                <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       class="form-control search-premium ps-5 pe-5" 
                       placeholder="🔍 Rechercher un exercice par libellé..."
                       style="font-size: 0.95rem;">
                @if($search)
                    <button wire:click="$set('search', '')" 
                            class="btn position-absolute top-50 end-0 translate-middle-y me-2 border-0 p-0"
                            style="width: 30px; height: 30px;">
                        <i class="fas fa-times-circle text-muted"></i>
                    </button>
                @endif
            </div>
        </div>

        <!-- Filtres et onglets -->
        <div class="glass-card rounded-4 p-3 animate-slide-in" style="animation-delay: 0.25s;">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <!-- Groupe gauche: Pagination + Réinitialiser -->
                <div class="d-flex align-items-center gap-3">
                    <!-- Pagination -->
                    <div class="d-flex align-items-center gap-2">
                        <label for="perPage" class="text-muted mb-0 fw-medium" style="font-size: 0.9rem; white-space: nowrap;">
                            Afficher :
                        </label>
                        <select wire:model.live="perPage" 
                                id="perPage"
                                class="form-select border-2" 
                                style="width: 85px; border-color: #dee2e6; border-radius: 10px; font-weight: 500; font-size: 0.9rem; padding: 0.5rem 0.75rem;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    
                    <!-- Réinitialiser -->
                    <button wire:click="resetFilters" 
                            class="btn btn-outline-secondary d-flex align-items-center gap-2"
                            style="border-radius: 10px; border-width: 2px; font-weight: 500; padding: 0.5rem 1.25rem; white-space: nowrap;">
                        <i class="fas fa-redo-alt" style="font-size: 0.85rem;"></i>
                        <span>Réinitialiser</span>
                    </button>
                </div>
                
                <!-- Groupe droite: Onglets de statut -->
                <div>
                    <div class="btn-group" role="group" style="box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-radius: 12px; overflow: hidden;">
                        <button @click="activeTab = 'all'" 
                                type="button"
                                :class="activeTab === 'all' ? 'btn-primary' : 'btn-outline-secondary'"
                                class="btn btn-sm px-4 d-flex align-items-center gap-2"
                                style="border: none; font-weight: 500; padding: 0.5rem 1.25rem; transition: all 0.2s;">
                            <i class="fas fa-th-list" style="font-size: 0.85rem;"></i>
                            <span>Tous</span>
                        </button>
                        <button @click="activeTab = 'active'" 
                                type="button"
                                :class="activeTab === 'active' ? 'btn-success' : 'btn-outline-secondary'"
                                class="btn btn-sm px-4 d-flex align-items-center gap-2"
                                style="border: none; font-weight: 500; padding: 0.5rem 1.25rem; transition: all 0.2s;">
                            <i class="fas fa-unlock" style="font-size: 0.85rem;"></i>
                            <span>Actifs</span>
                        </button>
                        <button @click="activeTab = 'closed'" 
                                type="button"
                                :class="activeTab === 'closed' ? 'btn-secondary' : 'btn-outline-secondary'"
                                class="btn btn-sm px-4 d-flex align-items-center gap-2"
                                style="border: none; font-weight: 500; padding: 0.5rem 1.25rem; transition: all 0.2s;">
                            <i class="fas fa-lock" style="font-size: 0.85rem;"></i>
                            <span>Fermés</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    --}}

    <!-- Tableau des exercices -->
    <div class="glass-card rounded-4 animate-slide-in" style="animation-delay: 0.3s;">
        <div class="p-4">
            <div class="d-flex align-items-center mb-4">
                <div class="flex-grow-1">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-list-ul me-2" style="color: #4361ee;"></i>
                        Liste des exercices
                    </h5>
                    <small class="text-muted">{{ $exercices->total() }} enregistrements trouvés</small>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table-premium w-100">
                    <thead>
                        <tr>
                            <th wire:click="sortBy('libelle')" class="sortable">
                                Libellé
                                @if($sortField === 'libelle')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('date_debut')" class="sortable">
                                Période
                                @if($sortField === 'date_debut' || $sortField === 'date_fin')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('statut')" class="sortable">
                                Statut
                                @if($sortField === 'statut')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('created_at')" class="sortable">
                                Création
                                @if($sortField === 'created_at')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($exercices as $exercice)
                            <tr wire:key="exercice-{{ $exercice->id }}" class="animate-slide-in">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <div class="rounded-circle p-2" 
                                                 style="background: {{ $exercice->estActif() ? 'rgba(6, 214, 160, 0.1)' : 'rgba(108, 117, 125, 0.1)' }}">
                                                <i class="fas fa-calendar-day" 
                                                   style="color: {{ $exercice->estActif() ? '#06d6a0' : '#6c757d' }}"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold">{{ $exercice->libelle }}</h6>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                {{ $exercice->date_debut->format('d/m/Y') }} - {{ $exercice->date_fin->format('d/m/Y') }}
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-calendar-week text-primary me-2"></i>
                                        <div>
                                            <span class="fw-medium">{{ $exercice->date_debut->format('M Y') }}</span>
                                            <span class="text-muted mx-1">→</span>
                                            <span class="fw-medium">{{ $exercice->date_fin->format('M Y') }}</span>
                                            <br>
                                            <small class="text-muted">{{ $exercice->periode }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($exercice->estActif())
                                        <span class="badge-premium bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                            <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                                            Actif
                                        </span>
                                    @else
                                        <span class="badge-premium bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">
                                            <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                                            Fermé
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-circle text-muted me-2"></i>
                                        <div>
                                            <span>{{ $exercice->created_at->format('d/m/Y') }}</span>
                                            <br>
                                            <small class="text-muted">{{ $exercice->created_at->format('H:i') }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-end gap-2">
                                        <button wire:click="edit({{ $exercice->id }})" 
                                                class="btn-action bg-info bg-opacity-10 text-info"
                                                title="Modifier l'exercice"
                                                wire:loading.attr="disabled">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        @if($exercice->estActif())
                                            <button wire:click="toggleStatut({{ $exercice->id }})" 
                                                    class="btn-action bg-warning bg-opacity-10 text-warning"
                                                    title="Fermer l'exercice"
                                                    wire:loading.attr="disabled">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        @else
                                            <button wire:click="toggleStatut({{ $exercice->id }})" 
                                                    class="btn-action bg-success bg-opacity-10 text-success"
                                                    title="Activer l'exercice"
                                                    wire:loading.attr="disabled">
                                                <i class="fas fa-unlock"></i>
                                            </button>
                                        @endif
                                        
                                        @if(!$exercice->estActif())
                                            <button wire:click="confirmDelete({{ $exercice->id }})" 
                                                    class="btn-action bg-danger bg-opacity-10 text-danger"
                                                    title="Supprimer l'exercice"
                                                    wire:loading.attr="disabled">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="empty-state">
                                        <div class="mb-4">
                                            <i class="fas fa-calendar-times fa-4x mb-3"></i>
                                            <h4 class="text-white mb-2">Aucun exercice trouvé</h4>
                                            <p class="text-white-50 mb-4">
                                                Commencez par créer votre premier exercice comptable
                                            </p>
                                        </div>
                                        <button wire:click="create" 
                                                class="btn btn-light btn-premium px-5">
                                            <i class="fas fa-plus-circle me-2"></i>
                                            Créer un exercice
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION COMMENTÉE TEMPORAIREMENT
            <!-- Pagination -->
            @if($exercices->hasPages())
                <div class="mt-4 d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Affichage de {{ $exercices->firstItem() }} à {{ $exercices->lastItem() }} sur {{ $exercices->total() }} exercices
                    </div>
                    <div class="pagination-premium">
                        {{ $exercices->onEachSide(1)->links() }}
                    </div>
                </div>
            @endif
            --}}
        </div>
    </div>

    <!-- Modal de création/édition - DESIGN MODERNE ET ÉPURÉ -->
    @if($showModal)
        <div class="modal-overlay" wire:click.self="$set('showModal', false)">
            <div class="modal-container">
                <div class="modal-modern">
                    <!-- Header simple et élégant -->
                    <div class="modal-modern-header">
                        <div>
                            <h2 class="modal-modern-title">
                                @if($isEditing)
                                    Modifier l'exercice comptable
                                @else
                                    Créer un nouvel exercice
                                @endif
                            </h2>
                            <p class="modal-modern-subtitle">
                                {{ $isEditing ? 'Mettez à jour les informations de l\'exercice' : 'Définissez une nouvelle période comptable' }}
                            </p>
                        </div>
                        <button type="button" 
                                class="modal-close-btn" 
                                wire:click="$set('showModal', false)"
                                aria-label="Fermer">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <form wire:submit.prevent="save">
                        <div class="modal-modern-body">
                            <!-- Libellé -->
                            <div class="form-group-modern">
                                <label for="libelle" class="form-label-modern">
                                    Libellé de l'exercice
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       wire:model="libelle" 
                                       id="libelle" 
                                       class="form-input-modern @error('libelle') is-invalid @enderror"
                                       placeholder="Ex: Exercice 2024, Année fiscale 2024-2025...">
                                @error('libelle')
                                    <span class="form-error-modern">
                                        <i class="fas fa-exclamation-circle me-1"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <!-- Dates en ligne -->
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label for="date_debut" class="form-label-modern">
                                            Date de début
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" 
                                               wire:model="date_debut" 
                                               id="date_debut" 
                                               class="form-input-modern @error('date_debut') is-invalid @enderror">
                                        @error('date_debut')
                                            <span class="form-error-modern">
                                                <i class="fas fa-exclamation-circle me-1"></i>
                                                {{ $message }}
                                            </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label for="date_fin" class="form-label-modern">
                                            Date de fin
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" 
                                               wire:model="date_fin" 
                                               id="date_fin" 
                                               class="form-input-modern @error('date_fin') is-invalid @enderror">
                                        @error('date_fin')
                                            <span class="form-error-modern">
                                                <i class="fas fa-exclamation-circle me-1"></i>
                                                {{ $message }}
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Statut - Design moderne et simple -->
                            <div class="form-group-modern">
                                <label class="form-label-modern mb-3">Statut de l'exercice</label>
                                
                                <div class="status-toggle-container">
                                    <div class="status-option" :class="{ 'status-option-active': !$wire.statut }">
                                        <div class="status-option-content">
                                            <div class="status-icon" style="background: #e9ecef; color: #6c757d;">
                                                <i class="fas fa-lock"></i>
                                            </div>
                                            <div class="status-info">
                                                <h6 class="status-title">Fermé</h6>
                                                <p class="status-description">L'exercice sera créé mais non actif</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="toggle-switch-wrapper">
                                        <label class="toggle-switch">
                                            <input type="checkbox" 
                                                   wire:model.live="statut" 
                                                   value="1"
                                                   class="toggle-input">
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="status-option" :class="{ 'status-option-active': $wire.statut }">
                                        <div class="status-option-content">
                                            <div class="status-icon" style="background: #d1f4e0; color: #06d6a0;">
                                                <i class="fas fa-unlock"></i>
                                            </div>
                                            <div class="status-info">
                                                <h6 class="status-title">Actif</h6>
                                                <p class="status-description">L'exercice sera utilisé immédiatement</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Avertissement si actif -->
                                @if($statut)
                                    <div class="alert-modern alert-warning">
                                        <div class="alert-icon">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                        <div class="alert-content">
                                            <strong>Attention :</strong> Un seul exercice peut être actif à la fois. L'exercice actuellement actif sera automatiquement fermé.
                                        </div>
                                    </div>
                                @endif

                                @error('statut')
                                    <span class="form-error-modern mt-2">
                                        <i class="fas fa-exclamation-circle me-1"></i>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Footer avec boutons -->
                        <div class="modal-modern-footer">
                            <button type="button" 
                                    class="btn-modern btn-secondary" 
                                    wire:click="$set('showModal', false)">
                                Annuler
                            </button>
                            <button type="submit" 
                                    class="btn-modern btn-primary"
                                    wire:loading.attr="disabled"
                                    wire:target="save">
                                <span wire:loading wire:target="save">
                                    <span class="spinner-border spinner-border-sm me-2"></span>
                                    Traitement...
                                </span>
                                <span wire:loading.remove wire:target="save">
                                    @if($isEditing)
                                        <i class="fas fa-check me-2"></i>
                                        Mettre à jour
                                    @else
                                        <i class="fas fa-plus me-2"></i>
                                        Créer l'exercice
                                    @endif
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal de suppression - VERSION CORRIGÉE -->
    @if($confirmingExerciceDeletion)
        <div class="modal-overlay" wire:click.self="$set('confirmingExerciceDeletion', false)">
            <div class="modal-premium animate-slide-in" style="max-width: 500px;">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <div class="text-center w-100">
                            <div class="rounded-circle bg-danger bg-opacity-10 p-3 d-inline-block mb-3">
                                <i class="fas fa-exclamation-triangle text-danger fa-3x"></i>
                            </div>
                            <h5 class="modal-title fw-bold fs-4 text-danger">Confirmer la suppression</h5>
                        </div>
                    </div>
                    <div class="modal-body text-center">
                        <p class="mb-0">Êtes-vous sûr de vouloir supprimer l'exercice</p>
                        <h6 class="fw-bold my-3 p-3 bg-light rounded-3">
                            @php
                                $exerciceToDelete = $exercices->firstWhere('id', $confirmingExerciceDeletion);
                            @endphp
                            {{ $exerciceToDelete->libelle ?? '' }}
                        </h6>
                        <div class="alert alert-danger bg-danger bg-opacity-10 border-0 rounded-4">
                            <i class="fas fa-ban me-2"></i>
                            Cette action est irréversible et supprimera définitivement l'exercice.
                        </div>
                    </div>
                    <div class="modal-footer border-0 justify-content-center">
                        <button type="button" 
                                class="btn btn-light btn-premium px-4" 
                                wire:click="$set('confirmingExerciceDeletion', false)">
                            <i class="fas fa-arrow-left me-2"></i>
                            Annuler
                        </button>
                        <button type="button" 
                                class="btn btn-danger btn-premium px-5"
                                wire:click="delete({{ $confirmingExerciceDeletion }})"
                                wire:loading.attr="disabled">
                            <span wire:loading wire:target="delete" class="spinner-border spinner-border-sm me-2"></span>
                            <span wire:loading.remove wire:target="delete">
                                <i class="fas fa-trash me-2"></i>
                            </span>
                            Supprimer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Loading overlay -->
    <div wire:loading.flex wire:target="save,delete,toggleStatut" class="loading-overlay">
        <div class="loading-content animate-slide-in">
            <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
            <h6 class="mb-0">Traitement en cours...</h6>
        </div>
    </div>
</div>