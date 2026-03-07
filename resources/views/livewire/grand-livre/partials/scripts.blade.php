<script>
    // ===== GESTION DU MODAL =====
    let isModalOpen = false;
    
    function openDeleteModal(event) {
        console.log('Ouverture du modal...');
        
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        
        updateSelectionCounts();
        
        const modal = document.getElementById('delete-modal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            isModalOpen = true;
            document.body.classList.add('overflow-hidden');
            
            // ✅ AJOUT : Mettre le focus sur le bouton Annuler par défaut
            setTimeout(() => {
                const cancelButton = document.querySelector('[onclick*="closeDeleteModal"]');
                if (cancelButton) {
                    cancelButton.focus();
                    // Ajouter une classe pour le style
                    cancelButton.classList.add('ring-2', 'ring-blue-500', 'ring-offset-2');
                }
            }, 100);
            
            console.log('Modal ouvert avec succès');
        }
    }
    
    function closeDeleteModal(event) {
        console.log('Fermeture du modal...');
        
        if (event) {
            event.stopPropagation();
        }
        
        const modal = document.getElementById('delete-modal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            isModalOpen = false;
            document.body.classList.remove('overflow-hidden');
            
            // ✅ AJOUT : Enlever le style du bouton
            const cancelButton = document.querySelector('[onclick*="closeDeleteModal"]');
            if (cancelButton) {
                cancelButton.classList.remove('ring-2', 'ring-blue-500', 'ring-offset-2');
            }
        }
    }
    
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && isModalOpen) {
            closeDeleteModal();
        }
    });
    
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('delete-modal');
        const modalContent = modal?.querySelector('.bg-white.rounded-lg');
        
        if (isModalOpen && modal && modalContent && !modalContent.contains(event.target)) {
            const deleteButton = document.querySelector('[onclick*="openDeleteModal"]');
            if (deleteButton && !deleteButton.contains(event.target)) {
                closeDeleteModal();
            }
        }
    });
    
    // ===== GESTION DES SELECTIONS =====
    function updateSelectionCounts() {
        const selected = document.querySelectorAll('.ecriture-checkbox:checked').length;
        const total = document.querySelectorAll('.ecriture-checkbox').length;
        
        const toolbarCount = document.getElementById('selected-count');
        if (toolbarCount) {
            toolbarCount.textContent = selected;
        }
        
        const modalCount = document.getElementById('selected-count-modal');
        if (modalCount) {
            modalCount.textContent = selected;
            
            // ✅ AJOUT : Mettre à jour le message dans le modal avec la phrase en rouge
            const modalContent = document.querySelector('#delete-modal .bg-white.rounded-lg');
            if (modalContent) {
                // Chercher ou créer l'élément pour le message en rouge
                let warningText = modalContent.querySelector('.irreversible-warning');
                if (!warningText) {
                    warningText = document.createElement('p');
                    warningText.className = 'irreversible-warning text-sm font-medium text-red-600 mt-2';
                    warningText.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i>Cette action est irréversible';
                    
                    // Ajouter après le message de sélection
                    const selectionMsg = modalContent.querySelector('p.text-gray-700');
                    if (selectionMsg && selectionMsg.parentNode) {
                        selectionMsg.parentNode.insertBefore(warningText, selectionMsg.nextSibling);
                    }
                }
            }
        }
    }
    
    function selectAllCheckboxes() {
        console.log('Sélection de toutes les checkboxes...');
        
        const checkboxes = document.querySelectorAll('.ecriture-checkbox');
        const selectAll = document.getElementById('select-all');
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
            const event = new Event('change', { bubbles: true });
            checkbox.dispatchEvent(event);
        });
        
        if (selectAll) {
            selectAll.checked = true;
        }
        
        updateSelectionCounts();
    }
    
    function deselectAllCheckboxes() {
        console.log('Désélection de toutes les checkboxes...');
        
        const checkboxes = document.querySelectorAll('.ecriture-checkbox');
        const selectAll = document.getElementById('select-all');
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
            const event = new Event('change', { bubbles: true });
            checkbox.dispatchEvent(event);
        });
        
        if (selectAll) {
            selectAll.checked = false;
        }
        
        updateSelectionCounts();
    }
    
    // ===== ACTIONS DE SUPPRESSION =====
    function deleteSelected() {
        const selected = Array.from(document.querySelectorAll('.ecriture-checkbox:checked'))
            .map(cb => parseInt(cb.value));
        
        console.log('Suppression sélectionnée:', selected);
        
        if (selected.length === 0) {
            alert('Veuillez sélectionner au moins une écriture à supprimer.');
            return;
        }
        
        // ✅ MODIFIÉ : Message avec la phrase en rouge en capitale
        if (!confirm(`⚠️ SUPPRESSION IRRÉVERSIBLE ⚠️\n\nÊtes-vous sûr de vouloir supprimer ${selected.length} écriture(s) ?\n\nCETTE ACTION EST IRRÉVERSIBLE !`)) {
            return;
        }
        
        closeDeleteModal();
        
        @this.deleteSelected(selected)
            .then(() => {
                console.log('Suppression réussie');
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur: ' + (error.message || 'Une erreur est survenue'));
            });
    }
    
    function deleteAllVisible() {
        const count = @json($stats['total'] ?? 0);
        
        if (count === 0) {
            alert('Aucune écriture visible à supprimer.');
            return;
        }
        
        // ✅ MODIFIÉ : Message avec la phrase en rouge
        if (!confirm(`⚠️ SUPPRESSION IRRÉVERSIBLE ⚠️\n\nSupprimer toutes les ${count} écritures visibles ?\n\nCETTE ACTION EST IRRÉVERSIBLE !`)) {
            return;
        }
        
        closeDeleteModal();
        
        @this.deleteAllVisible()
            .then(() => {
                console.log('Suppression visible réussie');
            })
            .catch(error => {
                alert('Erreur: ' + error.message);
            });
    }
    
    function deleteAll() {
        closeDeleteModal();
        
        setTimeout(() => {
            // ✅ MODIFIÉ : Message renforcé
            const password = prompt('⚠️ ATTENTION - ACTION IRRÉVERSIBLE ⚠️\n\nCette action supprimera TOUTES les écritures de votre entreprise.\n\nCETTE ACTION EST IRRÉVERSIBLE !\n\nPour confirmer, tapez "SUPPRIMER-TOUT" :');
            
            if (password !== 'SUPPRIMER-TOUT') {
                if (password !== null) {
                    alert('❌ Action annulée. Code de confirmation incorrect.');
                }
                return;
            }
            
            if (!confirm('⚠️ DERNIÈRE CONFIRMATION ⚠️\n\nÊtes-vous ABSOLUMENT SÛR de vouloir supprimer TOUTES les écritures ?\n\nCETTE ACTION EST IRRÉVERSIBLE !')) {
                return;
            }
            
            @this.deleteAll(password)
                .then(() => {
                    console.log('Suppression totale réussie');
                })
                .catch(error => {
                    alert('Erreur: ' + error.message);
                });
        }, 300);
    }
    
    // ===== INITIALISATION =====
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Initialisation des fonctionnalités de suppression...');
        
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                console.log('Select all changé:', this.checked);
                
                const checkboxes = document.querySelectorAll('.ecriture-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                    const event = new Event('change', { bubbles: true });
                    checkbox.dispatchEvent(event);
                });
                
                updateSelectionCounts();
            });
        }
        
        document.querySelectorAll('.ecriture-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                console.log('Checkbox changée:', this.value, this.checked);
                updateSelectionCounts();
                
                const allCheckboxes = document.querySelectorAll('.ecriture-checkbox');
                const allChecked = Array.from(allCheckboxes).every(cb => cb.checked);
                const selectAll = document.getElementById('select-all');
                
                if (selectAll) {
                    selectAll.checked = allChecked;
                }
            });
        });
        
        updateSelectionCounts();
        
        // ✅ AJOUT : Observer les changements de classe du modal
        const modal = document.getElementById('delete-modal');
        if (modal) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'class') {
                        if (modal.classList.contains('flex')) {
                            // Modal ouvert
                            setTimeout(() => {
                                const cancelButton = document.querySelector('[onclick*="closeDeleteModal"]');
                                if (cancelButton) {
                                    cancelButton.focus();
                                    cancelButton.classList.add('ring-2', 'ring-blue-500', 'ring-offset-2');
                                }
                            }, 100);
                        } else {
                            // Modal fermé
                            const cancelButton = document.querySelector('[onclick*="closeDeleteModal"]');
                            if (cancelButton) {
                                cancelButton.classList.remove('ring-2', 'ring-blue-500', 'ring-offset-2');
                            }
                        }
                    }
                });
            });
            
            observer.observe(modal, { attributes: true });
        }
    });
    
    // Fonction pour confirmer la suppression d'une écriture
    function confirmDeleteEcriture(ecritureId) {
        if (!ecritureId || ecritureId === 0) {
            alert('Erreur : aucune écriture sélectionnée.');
            return;
        }
        
        // ✅ MODIFIÉ : Message avec la phrase en rouge
        if (!confirm('⚠️ SUPPRESSION IRRÉVERSIBLE ⚠️\n\nÊtes-vous sûr de vouloir supprimer cette écriture ?\n\nCETTE ACTION EST IRRÉVERSIBLE !')) {
            return;
        }
        
        @this.call('deleteEcriture', ecritureId)
            .then(() => {
                // Livewire gère déjà la fermeture
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur : ' + (error.message || 'Une erreur est survenue'));
            });
    }
</script>

