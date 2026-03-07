<x-app-layout>
<div class="row">
    <div class="col-md-6">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <h5 class="card-title">Entreprises</h5>
                <h2 class="card-text">{{ $entrepriseCount }}</h2>
                <a href="{{ route('admin.entreprises') }}" class="text-white">Gérer les entreprises</a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <h5 class="card-title">Utilisateurs</h5>
                <h2 class="card-text">{{ $userCount }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4>Actions rapides</h4>
    </div>
    <div class="card-body">
        <a href="{{ route('admin.entreprises.create') }}" class="btn btn-primary me-2">
            <i class="bi bi-building-add"></i> Ajouter une entreprise
        </a>
        <a href="{{ route('admin.comptes') }}" class="btn btn-secondary">
            <i class="bi bi-cash-coin"></i> Gérer les comptes
        </a>
    </div>
</div>
</x-app-layout>