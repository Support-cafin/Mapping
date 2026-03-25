{{-- resources/views/admin/entreprises/create.blade.php --}}
<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <div class="max-w-2xl mx-auto">
            <!-- En-tête -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Ajouter une nouvelle ONG</h1>
                <p class="text-gray-600 mt-1">Remplissez les informations de l'ONG</p>
            </div>

            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <form action="{{ route('admin.entreprises.store') }}" method="POST">
                    @csrf
                    
                    <!-- Informations principales -->
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Informations principales</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Nom -->
                            <div>
                                <label for="nom" class="block text-sm font-medium text-gray-700 mb-1">
                                    Nom de l'ONG *
                                </label>
                                <input type="text" 
                                       id="nom" 
                                       name="nom" 
                                       value="{{ old('nom') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       required
                                       placeholder="Ex: Société ABC">
                                @error('nom')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <!-- Code -->
                            <div>
                                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">
                                    Code unique *
                                </label>
                                <input type="text" 
                                       id="code" 
                                       name="code" 
                                       value="{{ old('code') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       required
                                       placeholder="Ex: ENT001">
                                <p class="mt-1 text-xs text-gray-500">Ce code doit être unique pour chaque ONG</p>
                                @error('code')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Informations de contact -->
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Informations de contact</h2>
                        
                        <div class="space-y-4">
                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                    Email
                                </label>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="contact@ong.com">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <!-- Téléphone -->
                            <div>
                                <label for="telephone" class="block text-sm font-medium text-gray-700 mb-1">
                                    Téléphone
                                </label>
                                <input type="tel" 
                                       id="telephone" 
                                       name="telephone" 
                                       value="{{ old('telephone') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="+225 01 23 45 67 89">
                                @error('telephone')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <!-- Adresse -->
                            <div>
                                <label for="adresse" class="block text-sm font-medium text-gray-700 mb-1">
                                    Adresse
                                </label>
                                <textarea id="adresse" 
                                          name="adresse" 
                                          rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                          placeholder="Adresse complète de l'ONG">{{ old('adresse') }}</textarea>
                                @error('adresse')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informations de contact -->
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Informations de l'ONG</h2>
                        
                        <div class="space-y-4">
                            <!-- Email -->
                            <div>
                                <label for="numero_fiscal" class="block text-sm font-medium text-gray-700 mb-1">
                                    N° fiscal
                                </label>
                                <input type="text" 
                                       id="numero_fiscal" 
                                       name="numero_fiscal" 
                                       value="{{ old('numero_fiscal') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="N° fiscal">
                                @error('numero_fiscal')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <!-- Téléphone -->
                            <div>
                                <label for="numero_agrement" class="block text-sm font-medium text-gray-700 mb-1">
                                    N° agrément
                                </label>
                                <input type="text" 
                                       id="numero_agrement" 
                                       name="numero_agrement" 
                                       value="{{ old('numero_agrement') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="N° agrément">
                                @error('numero_agrement')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <!-- Adresse -->
                            <div>
                                <label for="sigle_usuel" class="block text-sm font-medium text-gray-700 mb-1">
                                    Sigle usuel
                                </label>
                                <input type="text" 
                                       id="sigle_usuel" 
                                       name="sigle_usuel" 
                                       value="{{ old('sigle_usuel') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Sigle usuel">
                                @error('sigle_usuel')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="registre_commerce" class="block text-sm font-medium text-gray-700 mb-1">
                                    Registre de commerce
                                </label>
                                <input type="text" 
                                       id="registre_commerce" 
                                       name="registre_commerce" 
                                       value="{{ old('registre_commerce') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Registre de commerce">
                                @error('registre_commerce')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <!-- Téléphone -->
                            <div>
                                <label for="numero_ninea" class="block text-sm font-medium text-gray-700 mb-1">
                                    N° NINEA
                                </label>
                                <input type="text" 
                                       id="numero_ninea" 
                                       name="numero_ninea" 
                                       value="{{ old('numero_ninea') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="N° NINEA">
                                @error('numero_ninea')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <!-- Adresse -->
                            <div>
                                <label for="adresse" class="block text-sm font-medium text-gray-700 mb-1">
                                    Pays
                                </label>
                                <select id="pays_id" 
                                        name="pays_id" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required>
                                    <option value="">Sélectionnez le pays de l'ONG</option>
                                    @php $pays = DB::table('pays')->orderBy('libelle_fr', 'asc')->get();  @endphp
                                                    @foreach($pays as $ong_pays)
                                                        <option value="{{ $ong_pays->libelle_fr }}" {{ old('pays_id') == $ong_pays->libelle_fr ? 'selected' : '' }}>
                                                            {{ $ong_pays->libelle_fr }}
                                                        </option>
                                                    @endforeach
                                </select>
                                @error('pays_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="adresse" class="block text-sm font-medium text-gray-700 mb-1">
                                    Type compte
                                </label>
                                <select id="type_compte" 
                                        name="type_compte" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required>
                                    <option value="">Sélectionnez le type(Sup Admin or Admin)</option>
                                    <option value="standard">Standard</option>
                                    <option value="premium">Premium</option>
                                </select>
                                @error('type_compte')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                        </div>
                    </div>

                    <!-- Informations administratives -->
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Administration</h2>
                        
                        <div class="space-y-4">
                            <!-- Créer un admin pour cette ONG -->
                            <div class="flex items-start">
                                <input type="checkbox" 
                                       id="create_admin" 
                                       name="create_admin" 
                                       value="1"
                                       {{ old('create_admin') ? 'checked' : 'checked' }}
                                       class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mt-1">
                                <div class="ml-3">
                                    <label for="create_admin" class="text-sm font-medium text-gray-700">
                                        Créer un administrateur pour cette ONG
                                    </label>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Un compte administrateur sera créé avec les droits de gestion pour cette ONG.
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Informations de l'admin (conditionnel) -->
                            <div id="adminFields" class="space-y-4 pl-7 border-l-2 border-blue-200">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Nom admin -->
                                    <div>
                                        <label for="admin_name" class="block text-sm font-medium text-gray-700 mb-1">
                                            Nom de l'administrateur
                                        </label>
                                        <input type="text" 
                                               id="admin_name" 
                                               name="admin_name" 
                                               value="{{ old('admin_name', 'Administrateur') }}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               placeholder="Ex: Jean Dupont">
                                    </div>
                                    
                                    <!-- Email admin -->
                                    <div>
                                        <label for="admin_email" class="block text-sm font-medium text-gray-700 mb-1">
                                            Email de l'administrateur
                                        </label>
                                        <input type="email" 
                                               id="admin_email" 
                                               name="admin_email" 
                                               value="{{ old('admin_email') }}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               placeholder="admin@ong.com">
                                    </div>
                                </div>
                                
                                <!-- Mot de passe admin -->
                                <div>
                                    <label for="admin_password" class="block text-sm font-medium text-gray-700 mb-1">
                                        Mot de passe temporaire
                                    </label>
                                    <input type="text" 
                                           id="admin_password" 
                                           name="admin_password" 
                                           value="{{ old('admin_password', 'Password123') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           readonly>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Ce mot de passe sera envoyé à l'administrateur. Il devra le changer à sa première connexion.
                                    </p>
                                    <button type="button" 
                                            onclick="generatePassword()" 
                                            class="mt-2 text-sm text-blue-600 hover:text-blue-800">
                                        Générer un nouveau mot de passe
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <div class="flex justify-between">
                            <a href="{{ route('admin.entreprises.index') }}" 
                               class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                                Annuler
                            </a>
                            <div class="space-x-3">
                                <button type="reset" 
                                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                                    Réinitialiser
                                </button>
                                <button type="submit" 
                                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                                    Créer l'ONG
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Informations importantes -->
            <div class="mt-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                <div class="flex items-start">
                    <ion-icon name="warning" class="text-yellow-600 text-xl mr-2 mt-0.5"></ion-icon>
                    <div>
                        <p class="text-sm text-yellow-800">
                            <strong>Important :</strong> 
                            Après la création de l'ONG, vous ne pourrez pas modifier le code. 
                            Assurez-vous qu'il soit unique et significatif. 
                            Il est recommandé de créer un administrateur pour chaque ONG.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const createAdminCheckbox = document.getElementById('create_admin');
        const adminFields = document.getElementById('adminFields');
        
        // Afficher/masquer les champs admin
        function toggleAdminFields() {
            if (createAdminCheckbox.checked) {
                adminFields.style.display = 'block';
            } else {
                adminFields.style.display = 'none';
            }
        }
        
        createAdminCheckbox.addEventListener('change', toggleAdminFields);
        toggleAdminFields(); // Initialiser
        
        // Générer un mot de passe
        window.generatePassword = function() {
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
            
            document.getElementById('admin_password').value = password;
        };
        
        // Générer un email admin basé sur le nom de l'ONG
        const nomInput = document.getElementById('nom');
        const emailAdminInput = document.getElementById('admin_email');
        
        nomInput.addEventListener('blur', function() {
            if (!emailAdminInput.value && nomInput.value) {
                // Créer un email basé sur le nom de l'ONG
                const nomNormalise = nomInput.value
                    .toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // Enlever les accents
                    .replace(/[^a-z0-9]/g, '.') // Remplacer les espaces par des points
                    .replace(/\.+/g, '.'); // Éviter les points multiples
                
                const email = `admin@${nomNormalise}.com`;
                emailAdminInput.value = email;
            }
        });
    });
    </script>
    @endpush
</x-app-layout>