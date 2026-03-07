
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-8 text-center">
        <div class="text-6xl text-red-500 mb-4">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-4">Donateur non trouvé</h1>
        <p class="text-gray-600 mb-6">Le donateur que vous recherchez n'existe pas ou a été supprimé.</p>
        <a href="{{ route('donateurs.index') }}" 
           class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            Retour à la liste des donateurs
        </a>
    </div>
</div>