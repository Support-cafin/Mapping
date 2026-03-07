<style>
    .table-scroll-container {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        position: relative;
    }

    .table-header-sticky {
        position: sticky;
        top: 0;
        z-index: 30;
        background-color: #f9fafb;
    }

    .table-scroll-container::-webkit-scrollbar {
        width: 6px;
    }

    .table-scroll-container::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 3px;
    }
    
    .loading-indicator {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        color: #6b7280;
    }
    
    .skeleton-row {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: .5; }
    }

    .manual-row {
        background: linear-gradient(90deg, #fffbeb 0%, #fef3c7 100%) !important;
        position: relative;
        box-shadow: inset 4px 0 0 #f59e0b;
    }
    
    .manual-row:hover {
        background: linear-gradient(90deg, #fef3c7 0%, #fde68a 100%) !important;
    }
    
    /* Checkbox styling */
    .ecriture-checkbox:checked {
        background-color: #dc2626;
        border-color: #dc2626;
    }
</style>