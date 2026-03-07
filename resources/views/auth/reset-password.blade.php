<x-guest-layout>
    <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
        <div class="h-1.5 bg-gradient-to-r from-[#1885F6] to-[#6AC0F6]"></div>

        <div class="p-8">
            <!-- Logo -->
            <div class="flex justify-center mb-6">
                <div class="w-14 h-14 rounded-full flex items-center justify-center shadow-lg">
                    <img src="{{ asset('images/alima.png') }}" alt="logo">
                </div>
            </div>

            <h2 class="text-3xl font-bold text-center text-gray-800">ALIMA-MALI</h2>
            <p class="text-center text-gray-600 mt-1 mb-8">Nouveau mot de passe</p>

            <!-- Messages d'erreur -->
            @if($errors->any())
                <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            @foreach($errors->all() as $error)
                                <p class="text-sm text-red-700">{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('password.store') }}" id="resetPasswordForm">
                @csrf

                <!-- Password Reset Token -->
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <!-- Email Address -->
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
                            value="{{ old('email', $request->email) }}"
                            placeholder="exemple@mail.com"
                            class="pl-10 w-full py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1885F6] focus:border-transparent transition"
                        >
                    </div>
                    @error('email')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe</label>
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
                            class="pl-10 w-full py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1885F6] focus:border-transparent transition"
                        >
                    </div>
                    @error('password')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input
                            type="password"
                            name="password_confirmation"
                            id="password_confirmation"
                            required
                            placeholder="••••••••"
                            class="pl-10 w-full py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1885F6] focus:border-transparent transition"
                        >
                    </div>
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    id="submitBtn"
                    class="w-full bg-gradient-to-r from-[#1885F6] to-[#6AC0F6] text-white py-3 rounded-lg font-semibold shadow-md hover:shadow-xl hover:-translate-y-0.5 transition"
                >
                    Réinitialiser le mot de passe
                </button>
            </form>

            <!-- Lien retour login -->
            <div class="text-center mt-6 pt-6 border-t border-gray-200">
                <a href="{{ route('login') }}" class="text-sm text-[#1885F6] hover:text-[#6AC0F6] transition">
                    <i class="fas fa-arrow-left mr-1"></i>
                    Retour à la connexion
                </a>
            </div>
        </div>
    </div>

    <!-- Script pour désactiver le bouton après clic -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('resetPasswordForm');
            const submitBtn = document.getElementById('submitBtn');
            
            if (form && submitBtn) {
                form.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Réinitialisation...';
                    submitBtn.classList.remove('hover:shadow-xl', 'hover:-translate-y-0.5');
                    submitBtn.classList.add('opacity-75');
                });
            }
        });
    </script>
</x-guest-layout>