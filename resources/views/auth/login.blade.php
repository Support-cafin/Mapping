<x-guest-layout>
    @push('styles')
        <style>
            .blocked-message {
                background-color: #fee2e2;
                border-left: 4px solid #ef4444;
                padding: 1rem;
                border-radius: 0.5rem;
                margin-bottom: 1rem;
            }
            .attempts-message {
                background-color: #f0f9ff;
                border-left: 4px solid #0ea5e9;
                padding: 1rem;
                border-radius: 0.5rem;
                margin-bottom: 1rem;
            }
            .attempts-warning {
                background-color: #fef3c7;
                border-left: 4px solid #f59e0b;
            }
            .attempts-danger {
                background-color: #fee2e2;
                border-left: 4px solid #ef4444;
            }
            .progress-bar {
                height: 6px;
                background-color: #e5e7eb;
                border-radius: 3px;
                overflow: hidden;
                margin-top: 8px;
            }
            .progress-bar-fill {
                height: 100%;
                background-color: #3b82f6;
                transition: width 1s linear;
            }
            .password-toggle {
                position: absolute;
                right: 12px;
                top: 50%;
                transform: translateY(-50%);
                background: none;
                border: none;
                color: #6b7280;
                cursor: pointer;
                padding: 4px;
                outline: none;
                transition: color 0.2s;
            }
            .password-toggle:hover {
                color: #1885F6;
            }
        </style>
    @endpush
    
    <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
        <div class="h-1.5 bg-gradient-to-r from-[#1885F6] to-[#6AC0F6]"></div>

        <div class="p-8">
            <!-- Logo -->
            <div class="flex justify-center mb-6">
                <div class="w-14 h-14 rounded-full flex items-center justify-center shadow-lg">
                    <!--<img src="{{ asset('images/alima.png') }}" alt="logo">-->
                </div>
            </div>

            <h2 class="text-3xl font-bold text-center text-gray-800">APP-MAPPING</h2>
            <p class="text-center text-gray-600 mt-1 mb-8">Accédez à votre espace personnel</p>

            <!-- Message de tentatives -->
            <div id="attemptsMessage" style="display: none;" class="attempts-message">
                <div class="flex items-center">
                    <i class="fas fa-shield-alt mr-2 text-blue-500"></i>
                    <div>
                        <p class="text-sm font-medium text-gray-800" id="attemptsText"></p>
                        <div class="progress-bar">
                            <div id="attemptsProgress" class="progress-bar-fill" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message de blocage -->
            <div id="blockedMessage" style="display: none;" class="blocked-message">
                <div class="flex items-center">
                    <i class="fas fa-ban mr-2 text-red-500"></i>
                    <div>
                        <p class="text-sm font-medium text-gray-800" id="blockedText"></p>
                        <div class="progress-bar">
                            <div id="blockedProgress" class="progress-bar-fill bg-red-500" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages d'erreur du serveur -->
            @if($errors->any())
                <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500">
                    @foreach($errors->all() as $error)
                        <p class="text-sm text-red-700">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @if (session('status'))
                <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500">
                    <p class="text-sm text-green-700">{{ session('status') }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="loginForm">
                @csrf

                <!-- Email -->
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Adresse Email</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fa-regular fa-envelope"></i>
                        </span>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            required
                            value="{{ old('email') }}"
                            placeholder="exemple@mail.com"
                            class="pl-10 w-full py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1885F6] focus:border-transparent transition"
                        >
                    </div>
                </div>

                <!-- Password avec bouton toggle -->
                <div class="mb-5">
                    <div class="flex justify-between items-center mb-1">
                        <label class="block text-sm font-medium text-gray-700">Mot de passe</label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm text-[#1885F6] hover:text-[#6AC0F6] transition">
                                Mot de passe oublié ?
                            </a>
                        @endif
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            required
                            placeholder="••••••••"
                            class="pl-10 pr-10 w-full py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1885F6] focus:border-transparent transition"
                        >
                        <!-- Bouton pour afficher/masquer le mot de passe -->
                        <button 
                            type="button" 
                            id="togglePassword" 
                            class="password-toggle"
                            aria-label="Afficher le mot de passe"
                        >
                            <i class="fas fa-eye"></i> 
                        </button>
                    </div>
                </div>

                <!-- Remember -->
                <label class="flex items-center mb-7 cursor-pointer gap-2">
                    <input type="checkbox" name="remember" class="rounded text-[#1885F6]">
                    <span class="text-gray-700">Se souvenir de moi</span>
                </label>

                <!-- Submit -->
                <button
                    type="submit"
                    id="submitBtn"
                    class="w-full bg-gradient-to-r from-[#1885F6] to-[#6AC0F6] text-white py-3 rounded-lg font-semibold shadow-md hover:shadow-xl hover:-translate-y-0.5 transition"
                >
                    Se connecter
                </button>
            </form>
        </div>
    </div>

    <!-- Script JavaScript avec fonctionnalité d'affichage du mot de passe -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Login security system initialized');
            
            // Fonctionnalité d'affichage/masquage du mot de passe
            const passwordInput = document.getElementById('password');
            const togglePasswordBtn = document.getElementById('togglePassword');
            
            if (togglePasswordBtn && passwordInput) {
                togglePasswordBtn.addEventListener('click', function() {
                    // Changer le type d'input
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    // Changer l'icône
                    const icon = this.querySelector('i');
                    if (type === 'text') {
                        icon.className = 'fas fa-eye-slash';
                        this.setAttribute('aria-label', 'Masquer le mot de passe');
                    } else {
                        icon.className = 'fas fa-eye';
                        this.setAttribute('aria-label', 'Afficher le mot de passe');
                    }
                });
            }
            
            // Variables pour le système de sécurité
            const emailInput = document.getElementById('email');
            const attemptsMessage = document.getElementById('attemptsMessage');
            const attemptsText = document.getElementById('attemptsText');
            const attemptsProgress = document.getElementById('attemptsProgress');
            const blockedMessage = document.getElementById('blockedMessage');
            const blockedText = document.getElementById('blockedText');
            const blockedProgress = document.getElementById('blockedProgress');
            const submitBtn = document.getElementById('submitBtn');
            
            let checkTimeout;
            let blockTimerInterval = null;
            
            // Fonction pour vérifier le statut de sécurité
            async function checkSecurityStatus(email) {
                if (!email) {
                    hideAllMessages();
                    enableForm();
                    return;
                }
                
                try {
                    console.log('Vérification sécurité pour:', email);
                    
                    const response = await fetch('/login-security-status', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ email: email })
                    });
                    
                    if (!response.ok) throw new Error('Erreur API');
                    
                    const data = await response.json();
                    console.log('Données sécurité:', data);
                    
                    updateUI(data);
                    
                } catch (error) {
                    console.error('Erreur vérification sécurité:', error);
                    hideAllMessages();
                    enableForm();
                }
            }
            
            // Fonction pour mettre à jour l'interface
            function updateUI(data) {
                // Arrêter tout timer précédent
                if (blockTimerInterval) {
                    clearInterval(blockTimerInterval);
                    blockTimerInterval = null;
                }
                
                // Cacher tous les messages d'abord
                hideAllMessages();
                
                // Si bloqué
                if (data.isBlocked && data.blockTime > 0) {
                    // S'assurer que le temps de blocage ne dépasse pas 120 secondes (2 minutes)
                    const blockTime = Math.min(data.blockTime, 120);
                    console.log(`Affichage blocage: ${blockTime} secondes`);
                    showBlockedMessage(blockTime);
                    return;
                }
                
                // Si des tentatives échouées
                if (data.attempts > 0) {
                    showAttemptsMessage(data.attempts);
                }
            }
            
            // Afficher le message de tentatives
            function showAttemptsMessage(attempts) {
                if (!attemptsMessage || !attemptsText) return;
                
                const remaining = 3 - attempts;
                
                if (remaining > 0) {
                    // Ajuster la classe CSS selon le nombre de tentatives
                    if (remaining === 1) {
                        attemptsMessage.className = 'attempts-message attempts-danger';
                        attemptsText.textContent = `⚠️ Attention : ${attempts} tentative(s) échouée(s). Il vous reste 1 seule tentative !`;
                    } else if (remaining === 2) {
                        attemptsMessage.className = 'attempts-message attempts-warning';
                        attemptsText.textContent = `⚠️ ${attempts} tentative(s) échouée(s). Il vous reste ${remaining} tentatives.`;
                    } else {
                        attemptsMessage.className = 'attempts-message';
                        attemptsText.textContent = `ℹ️ ${attempts} tentative(s) échouée(s). Il vous reste ${remaining} tentatives.`;
                    }
                    
                    // Calculer la progression (0 à 3 tentatives = 0% à 100%)
                    const progress = (attempts / 3) * 100;
                    attemptsProgress.style.width = `${progress}%`;
                    
                    attemptsMessage.style.display = 'block';
                    enableForm();
                }
            }
            
            // Afficher le message de blocage
            function showBlockedMessage(seconds) {
                if (!blockedMessage || !blockedText || !blockedProgress) return;
                
                blockedMessage.style.display = 'block';
                disableForm();
                
                let remaining = Math.min(seconds, 120); // Maximum 120 secondes
                
                updateBlockedTimer(remaining);
                
                // Mettre à jour le compte à rebours chaque seconde
                blockTimerInterval = setInterval(() => {
                    remaining--;
                    updateBlockedTimer(remaining);
                    
                    if (remaining <= 0) {
                        clearInterval(blockTimerInterval);
                        blockTimerInterval = null;
                        setTimeout(() => {
                            blockedMessage.style.display = 'none';
                            enableForm();
                            if (emailInput.value) {
                                checkSecurityStatus(emailInput.value);
                            }
                        }, 1000);
                    }
                }, 1000);
            }
            
            function updateBlockedTimer(secs) {
                const minutes = Math.floor(secs / 60);
                const secondsRemaining = secs % 60;
                
                // Formater l'affichage
                let timeText = '';
                if (minutes > 0) {
                    timeText = `${minutes}m ${secondsRemaining}s`;
                } else {
                    timeText = `${secondsRemaining}s`;
                }
                
                blockedText.textContent = `⛔ Compte bloqué. Réessayez dans ${timeText}`;
                
                // Mettre à jour la barre de progression (120 secondes max)
                const progress = Math.max(0, 100 - (secs / 120 * 100));
                blockedProgress.style.width = `${progress}%`;
            }
            
            // Cacher tous les messages
            function hideAllMessages() {
                if (attemptsMessage) attemptsMessage.style.display = 'none';
                if (blockedMessage) blockedMessage.style.display = 'none';
            }
            
            // Désactiver le formulaire
            function disableForm() {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Accès bloqué (2 min)';
                    submitBtn.classList.remove('hover:shadow-xl', 'hover:-translate-y-0.5', 'from-[#1885F6]', 'to-[#6AC0F6]');
                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed', 'bg-gray-400');
                }
                
                if (emailInput) emailInput.disabled = true;
                if (passwordInput) passwordInput.disabled = true;
            }
            
            // Activer le formulaire
            function enableForm() {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Se connecter';
                    submitBtn.classList.add('hover:shadow-xl', 'hover:-translate-y-0.5', 'from-[#1885F6]', 'to-[#6AC0F6]');
                    submitBtn.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-gray-400');
                }
                
                if (emailInput) emailInput.disabled = false;
                if (passwordInput) passwordInput.disabled = false;
            }
            
            // Événements pour le système de sécurité
            if (emailInput) {
                // Vérifier au chargement si email présent
                if (emailInput.value) {
                    setTimeout(() => checkSecurityStatus(emailInput.value), 300);
                }
                
                // Vérifier lors de la saisie
                emailInput.addEventListener('input', function() {
                    clearTimeout(checkTimeout);
                    checkTimeout = setTimeout(() => {
                        checkSecurityStatus(this.value);
                    }, 300);
                });
            }
            
            // Empêcher la soumission si bloqué
            const loginForm = document.getElementById('loginForm');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    if (blockedMessage.style.display === 'block') {
                        e.preventDefault();
                        console.log('Soumission bloquée : compte temporairement bloqué (2 minutes)');
                        return false;
                    }
                    return true;
                });
            }
            
            console.log('Système de sécurité prêt avec affichage/masquage du mot de passe');
        });
    </script>
    <style>
    /* CSS pour positionner le bouton */
    .password-container {
        position: relative;
    }
    .password-toggle-btn {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #6b7280;
        cursor: pointer;
        padding: 4px;
    }
    .password-toggle-btn:hover {
        color: #1885F6;
    }
    .password-field {
        padding-right: 40px !important;
    }
</style>

<script>
    // Script simple pour ajouter le bouton
    setTimeout(function() {
        const passwordField = document.getElementById('password');
        if (passwordField) {
            // Ajouter la classe pour le padding
            passwordField.classList.add('password-field');
            
            // Créer le bouton
            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.className = 'password-toggle-btn';
            toggleBtn.innerHTML = '👁️';
            toggleBtn.title = 'Afficher/Masquer le mot de passe';
            
            // Positionner le bouton
            passwordField.parentNode.style.position = 'relative';
            passwordField.parentNode.appendChild(toggleBtn);
            
            // Ajouter la fonctionnalité
            toggleBtn.addEventListener('click', function() {
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    this.innerHTML = '🙈';
                    this.title = 'Masquer le mot de passe';
                } else {
                    passwordField.type = 'password';
                    this.innerHTML = '👁️';
                    this.title = 'Afficher le mot de passe';
                }
            });
        }
    }, 100); // Petit délai pour être sûr que le DOM est prêt
</script>
</x-guest-layout>