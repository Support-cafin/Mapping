<x-app-layout>
<div class="container mx-auto px-4 py-6">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">
            Ajouter un utilisateur
        </h1>
        
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf
            
            <div class="space-y-4">
                <!-- Nom -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Nom complet *
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="{{ old('name') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           required
                           placeholder="Ex: Jean Dupont">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        Email *
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="{{ old('email') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           required
                           placeholder="exemple@entreprise.com">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Mot de passe -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        Mot de passe *
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                        <button type="button" 
                                onclick="togglePassword('password')" 
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <ion-icon name="eye" class="text-lg"></ion-icon>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Confirmation mot de passe -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                        Confirmer le mot de passe *
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                        <button type="button" 
                                onclick="togglePassword('password_confirmation')" 
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <ion-icon name="eye" class="text-lg"></ion-icon>
                        </button>
                    </div>
                </div>
                
                <!-- Entreprise (POUR TOUS LES ADMINS) -->
                <div>
                    <label for="entreprise_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Entreprise *
                    </label>
                    <select id="entreprise_id" 
                            name="entreprise_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                        <option value="">Sélectionnez une entreprise</option>
                        @php $entre = DB::table('entreprises')->where('id', Auth::user()->entreprise_id)->first();  @endphp
                                @if($entre->id)
                                    <option value="{{ $entre->id }}" {{ old('entreprise_id') == $entre->id ? 'selected' : '' }}>
                                                {{ $entre->nom }} ({{ $entre->code }})
                                    </option>
                                @else
                                    @if(Auth::user()->is_admin == 1 AND Auth::user()->is_Super_admin == 1)
                                        @foreach($entreprises as $entreprise)
                                            <option value="{{ $entreprise->id }}" {{ old('entreprise_id') == $entreprise->id ? 'selected' : '' }}>
                                                {{ $entreprise->nom }} ({{ $entreprise->code }})
                                            </option>
                                        @endforeach
                                    @endif
                                @endif
                    </select>
                    @error('entreprise_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Rôle admin (POUR TOUS LES ADMINS) -->
                <div class="flex items-center">
                    <input type="checkbox" 
                           id="is_admin" 
                           name="is_admin" 
                           value="1"
                           {{ old('is_admin') ? 'checked' : '' }}
                           class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="is_admin" class="ml-2 text-sm text-gray-700">
                        Définir comme administrateur
                    </label>
                </div>
                
                <!-- Option: Générer un mot de passe -->
                <div class="pt-2">
                    <button type="button" 
                            onclick="generatePassword()" 
                            class="text-sm text-blue-600 hover:text-blue-800 inline-flex items-center">
                        <ion-icon name="refresh" class="mr-1"></ion-icon>
                        Générer un mot de passe sécurisé
                    </button>
                </div>
            </div>
            
            <!-- Boutons -->
            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('admin.users.index') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                    Annuler
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                    Créer l'utilisateur
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.nextElementSibling.querySelector('ion-icon');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.name = 'eye-off';
        } else {
            input.type = 'password';
            icon.name = 'eye';
        }
    }
    
    function generatePassword() {
        const length = 12;
        const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
        let password = "";
        
        // Assurer au moins un chiffre, une majuscule et un caractère spécial
        password += "ABCDEFGHIJKLMNOPQRSTUVWXYZ"[Math.floor(Math.random() * 26)];
        password += "0123456789"[Math.floor(Math.random() * 10)];
        password += "!@#$%^&*"[Math.floor(Math.random() * 8)];
        
        for (let i = 3; i < length; i++) {
            password += charset.charAt(Math.floor(Math.random() * charset.length));
        }
        
        // Mélanger le mot de passe
        password = password.split('').sort(() => 0.5 - Math.random()).join('');
        
        // Remplir les champs de mot de passe
        document.getElementById('password').value = password;
        document.getElementById('password_confirmation').value = password;
        
        // Changer le type pour montrer le mot de passe
        document.getElementById('password').type = 'text';
        document.getElementById('password_confirmation').type = 'text';
        
        // Mettre à jour les icônes
        document.getElementById('password').nextElementSibling.querySelector('ion-icon').name = 'eye-off';
        document.getElementById('password_confirmation').nextElementSibling.querySelector('ion-icon').name = 'eye-off';
    }
</script>
@endpush
</x-app-layout>