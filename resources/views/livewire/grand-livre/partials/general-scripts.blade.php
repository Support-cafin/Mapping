<script>
async function exportPdfImproved(button) {
    // Désactiver le bouton et montrer l'indicateur de chargement
    button.disabled = true;
    button.querySelector('.pdf-loading').classList.remove('hidden');
    const originalText = button.querySelector('.pdf-text').textContent;
    button.querySelector('.pdf-text').textContent = 'Génération...';
    
    try {
        const url = button.getAttribute('data-url');
        
        // Méthode 1: Utiliser fetch + blob (évite window.open)
        const response = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
        });
        
        if (!response.ok) {
            throw new Error(`Erreur ${response.status}: ${response.statusText}`);
        }
        
        // Vérifier si c'est un PDF
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/pdf')) {
            throw new Error('Le serveur n\'a pas retourné un PDF');
        }
        
        // Récupérer le blob
        const blob = await response.blob();
        
        // Créer un URL pour le blob
        const blobUrl = window.URL.createObjectURL(blob);
        
        // Créer un lien invisible pour le téléchargement
        const a = document.createElement('a');
        a.href = blobUrl;
        
        // Extraire le nom de fichier des headers
        let filename = 'grand_livre.pdf';
        const contentDisposition = response.headers.get('content-disposition');
        if (contentDisposition) {
            const filenameMatch = contentDisposition.match(/filename="?([^"]+)"?/);
            if (filenameMatch && filenameMatch[1]) {
                filename = filenameMatch[1];
            }
        }
        
        a.download = filename;
        
        // Ajouter au DOM, cliquer, et supprimer
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        
        // Libérer l'URL
        window.URL.revokeObjectURL(blobUrl);
        
    } catch (error) {
        console.error('Erreur lors du téléchargement:', error);
        
        // Fallback: Ouvrir dans un nouvel onglet (peut causer une page blanche)
        const url = button.getAttribute('data-url');
        window.open(url, '_blank');
        
        // Avertir l'utilisateur
        setTimeout(() => {
            alert('Le PDF est en cours de génération. Il s\'ouvrira dans un nouvel onglet.');
        }, 500);
    } finally {
        // Réactiver le bouton après un court délai
        setTimeout(() => {
            button.disabled = false;
            button.querySelector('.pdf-loading').classList.add('hidden');
            button.querySelector('.pdf-text').textContent = originalText;
        }, 1000);
    }
}
</script>