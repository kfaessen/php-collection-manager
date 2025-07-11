/**
 * Collection Manager - JavaScript Application
 */

// Global variables
let html5QrcodeScanner = null;
let currentMetadata = null;

// Initialize application when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

/**
 * Initialize the application
 */
function initializeApp() {
    console.log('Collectiebeheer app geïnitialiseerd');
    
    // Initialize event listeners
    initializeEventListeners();
    
    // Initialize modal event handlers
    initializeModalHandlers();
    
    // Check for WebRTC support
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        console.warn('WebRTC wordt niet ondersteund in deze browser');
        const startScanBtn = document.getElementById('start-scan');
        if (startScanBtn) {
            startScanBtn.textContent = 'Camera niet beschikbaar';
            startScanBtn.disabled = true;
        }
    }
}

/**
 * Initialize event listeners
 */
function initializeEventListeners() {
    // Start scan button
    const startScanBtn = document.getElementById('start-scan');
    if (startScanBtn) {
        startScanBtn.addEventListener('click', startScanning);
    }
    
    // Stop scan button
    const stopScanBtn = document.getElementById('stop-scan');
    if (stopScanBtn) {
        stopScanBtn.addEventListener('click', stopScanning);
    }
    
    // Manual barcode input
    const manualBarcode = document.getElementById('manual-barcode');
    if (manualBarcode) {
        manualBarcode.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                lookupBarcode();
            }
        });
    }
    
    // Form validation
    const manualForm = document.getElementById('manual-form');
    if (manualForm) {
        const inputs = manualForm.querySelectorAll('input[required], select[required]');
        inputs.forEach(input => {
            input.addEventListener('input', validateManualForm);
        });
    }
}

/**
 * Initialize modal handlers
 */
function initializeModalHandlers() {
    const addItemModal = document.getElementById('addItemModal');
    if (addItemModal) {
        addItemModal.addEventListener('hidden.bs.modal', function() {
            resetModal();
        });
        
        addItemModal.addEventListener('shown.bs.modal', function() {
            // Focus op het eerste input veld als handmatige tab actief is
            const manualTab = document.getElementById('manual-tab');
            if (manualTab && manualTab.classList.contains('active')) {
                const firstInput = document.getElementById('manual-title');
                if (firstInput) firstInput.focus();
            }
        });
    }
}

/**
 * Start barcode scanning
 */
function startScanning() {
    const qrReaderElement = document.getElementById('qr-reader');
    if (!qrReaderElement) return;
    
    try {
        html5QrcodeScanner = new Html5QrcodeScanner(
            "qr-reader",
            { 
                fps: 10, 
                qrbox: { width: 250, height: 250 },
                rememberLastUsedCamera: true,
                // Support various barcode formats
                supportedScanTypes: [
                    Html5QrcodeScanType.SCAN_TYPE_CAMERA
                ]
            },
            false
        );
        
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        
        // Update UI
        document.getElementById('start-scan').style.display = 'none';
        document.getElementById('stop-scan').style.display = 'inline-block';
        
    } catch (error) {
        console.error('Error starting scanner:', error);
        showToast('Fout bij starten van de scanner', 'error');
    }
}

/**
 * Stop barcode scanning
 */
function stopScanning() {
    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear().then(() => {
            html5QrcodeScanner = null;
            
            // Update UI
            document.getElementById('start-scan').style.display = 'inline-block';
            document.getElementById('stop-scan').style.display = 'none';
            
        }).catch(error => {
            console.error('Error stopping scanner:', error);
        });
    }
}

/**
 * Handle successful barcode scan
 */
function onScanSuccess(decodedText, decodedResult) {
    console.log('Barcode gescand:', decodedText);
    
    // Stop scanning
    stopScanning();
    
    // Set barcode value
    const manualBarcodeInput = document.getElementById('manual-barcode');
    if (manualBarcodeInput) {
        manualBarcodeInput.value = decodedText;
    }
    
    // Lookup metadata
    lookupBarcode(decodedText);
}

/**
 * Handle scan failure
 */
function onScanFailure(error) {
    // Deze functie wordt vaak aangeroepen tijdens scanning, dus we loggen alleen echte errors
    if (error && !error.includes('No QR code found')) {
        console.warn('Scan error:', error);
    }
}

/**
 * Lookup barcode metadata
 */
function lookupBarcode(barcode = null) {
    if (!barcode) {
        const manualBarcodeInput = document.getElementById('manual-barcode');
        if (!manualBarcodeInput) return;
        barcode = manualBarcodeInput.value.trim();
    }
    
    if (!barcode) {
        showToast('Voer een barcode in', 'warning');
        return;
    }
    
    // Validate barcode format
    if (!/^[0-9]{8,14}$/.test(barcode)) {
        showToast('Ongeldige barcode. Gebruik 8-14 cijfers.', 'error');
        return;
    }
    
    showLoading(true);
    
    // AJAX request to lookup metadata
    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=scan_barcode&barcode=${encodeURIComponent(barcode)}`
    })
    .then(response => response.json())
    .then(data => {
        showLoading(false);
        
        if (data.success) {
            currentMetadata = data.data;
            displayMetadataPreview(data.data);
            showToast(data.message, 'success');
        } else {
            showToast(data.error || 'Fout bij ophalen metadata', 'error');
        }
    })
    .catch(error => {
        showLoading(false);
        console.error('Error:', error);
        showToast('Netwerkfout bij ophalen metadata', 'error');
    });
}

/**
 * Display metadata preview
 */
function displayMetadataPreview(metadata) {
    const previewElement = document.getElementById('metadata-preview');
    if (!previewElement) return;
    
    // Update preview fields
    const title = document.getElementById('preview-title');
    const type = document.getElementById('preview-type');
    const platform = document.getElementById('preview-platform');
    const description = document.getElementById('preview-description');
    const cover = document.getElementById('preview-cover');
    
    if (title) title.textContent = metadata.title || 'Onbekend';
    if (type) type.textContent = metadata.type || 'Onbekend';
    if (platform) platform.textContent = metadata.director || metadata.platform || metadata.publisher || 'Onbekend';
    if (description) description.textContent = metadata.description || 'Geen beschrijving beschikbaar';
    
    if (cover && metadata.cover_image) {
        cover.src = metadata.cover_image;
        cover.style.display = 'block';
    } else if (cover) {
        cover.style.display = 'none';
    }
    
    // Show preview
    previewElement.style.display = 'block';
    previewElement.classList.add('fade-in');
    
    // Enable save button
    const saveBtn = document.getElementById('save-item');
    if (saveBtn) {
        saveBtn.disabled = false;
    }
}

/**
 * Save item to collection
 */
function saveItem() {
    let data = {};
    
    if (currentMetadata) {
        // Use scanned metadata
        data = {
            title: currentMetadata.title,
            type: currentMetadata.type,
            barcode: document.getElementById('manual-barcode').value,
            platform: currentMetadata.platform || '',
            director: currentMetadata.director || '',
            publisher: currentMetadata.publisher || '',
            description: currentMetadata.description || '',
            cover_image: currentMetadata.cover_image || '',
            metadata: JSON.stringify(currentMetadata.metadata || {})
        };
    } else {
        // Use manual form data
        data = {
            title: document.getElementById('manual-title').value,
            type: document.getElementById('manual-type').value,
            barcode: '',
            platform: document.getElementById('manual-platform').value,
            director: '',
            publisher: document.getElementById('manual-publisher').value,
            description: document.getElementById('manual-description').value,
            cover_image: document.getElementById('manual-cover').value,
            metadata: '{}'
        };
        
        // Basic validation
        if (!data.title || !data.type) {
            showToast('Titel en type zijn verplicht', 'error');
            return;
        }
    }
    
    showLoading(true);
    
    // Create form data
    const formData = new URLSearchParams();
    formData.append('action', 'add_item');
    Object.keys(data).forEach(key => {
        formData.append(key, data[key]);
    });
    
    // AJAX request to save item
    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        showLoading(false);
        
        if (data.success) {
            showToast(data.message, 'success');
            
            // Close modal and reload page
            const modal = bootstrap.Modal.getInstance(document.getElementById('addItemModal'));
            modal.hide();
            
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(data.error || 'Fout bij opslaan item', 'error');
        }
    })
    .catch(error => {
        showLoading(false);
        console.error('Error:', error);
        showToast('Netwerkfout bij opslaan item', 'error');
    });
}

/**
 * View item details
 */
function viewItem(id) {
    // Voor nu simpele implementatie - later kan modal met details worden toegevoegd
    showToast('Bekijk functionaliteit wordt nog geïmplementeerd', 'info');
}

/**
 * Delete item from collection
 */
function deleteItem(id) {
    if (!confirm('Weet u zeker dat u dit item wilt verwijderen?')) {
        return;
    }
    
    showLoading(true);
    
    const formData = new URLSearchParams();
    formData.append('action', 'delete_item');
    formData.append('id', id);
    
    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        showLoading(false);
        
        if (data.success) {
            showToast(data.message, 'success');
            
            // Reload page
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(data.error || 'Fout bij verwijderen item', 'error');
        }
    })
    .catch(error => {
        showLoading(false);
        console.error('Error:', error);
        showToast('Netwerkfout bij verwijderen item', 'error');
    });
}

/**
 * Validate manual form
 */
function validateManualForm() {
    const form = document.getElementById('manual-form');
    const saveBtn = document.getElementById('save-item');
    
    if (!form || !saveBtn) return;
    
    const title = document.getElementById('manual-title').value.trim();
    const type = document.getElementById('manual-type').value;
    
    const isValid = title && type;
    
    if (isValid && !currentMetadata) {
        saveBtn.disabled = false;
    } else if (!currentMetadata) {
        saveBtn.disabled = true;
    }
}

/**
 * Reset modal state
 */
function resetModal() {
    // Stop scanning if active
    stopScanning();
    
    // Clear metadata
    currentMetadata = null;
    
    // Hide preview
    const previewElement = document.getElementById('metadata-preview');
    if (previewElement) {
        previewElement.style.display = 'none';
    }
    
    // Clear forms
    const manualBarcode = document.getElementById('manual-barcode');
    if (manualBarcode) manualBarcode.value = '';
    
    const manualForm = document.getElementById('manual-form');
    if (manualForm) manualForm.reset();
    
    // Disable save button
    const saveBtn = document.getElementById('save-item');
    if (saveBtn) {
        saveBtn.disabled = true;
    }
    
    // Reset to scanner tab
    const scannerTab = document.getElementById('scanner-tab');
    if (scannerTab) {
        scannerTab.click();
    }
}

/**
 * Show loading spinner
 */
function showLoading(show) {
    const spinner = document.getElementById('loading-spinner');
    if (spinner) {
        spinner.style.display = show ? 'block' : 'none';
    }
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    // Create toast element if it doesn't exist
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    // Create toast
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="bi bi-${getToastIcon(type)} text-${getToastColor(type)} me-2"></i>
                <strong class="me-auto">Collectiebeheer</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('afterbegin', toastHtml);
    
    // Show toast
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: type === 'error' ? 5000 : 3000
    });
    
    toast.show();
    
    // Remove from DOM after hide
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

/**
 * Get toast icon based on type
 */
function getToastIcon(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    
    return icons[type] || icons.info;
}

/**
 * Get toast color based on type
 */
function getToastColor(type) {
    const colors = {
        success: 'success',
        error: 'danger',
        warning: 'warning',
        info: 'primary'
    };
    
    return colors[type] || colors.info;
}

/**
 * Initialize search functionality
 */
function initializeSearch() {
    const searchForm = document.querySelector('form[method="GET"]');
    if (searchForm) {
        const searchInput = searchForm.querySelector('input[name="search"]');
        if (searchInput) {
            // Add debounced search
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (this.value.length >= 3 || this.value.length === 0) {
                        searchForm.submit();
                    }
                }, 500);
            });
        }
    }
}

// Call initialize search when DOM is loaded
document.addEventListener('DOMContentLoaded', initializeSearch);

/**
 * Logout user
 */
function logout() {
    if (confirm('Weet u zeker dat u wilt uitloggen?')) {
        // Direct redirect naar logout.php (betrouwbaarder)
        window.location.href = 'logout.php';
    }
} 