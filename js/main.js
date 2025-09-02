// Fungsi untuk menambahkan produk ke keranjang
function addToCart(productId) {
    fetch('cart_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add&product_id=${productId}&quantity=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update badge keranjang
            updateCartBadge();
            
            // Tampilkan notifikasi sukses
            showNotification('Produk berhasil ditambahkan ke keranjang!', 'success');
        } else {
            showNotification(data.message || 'Error menambahkan ke keranjang', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error menambahkan ke keranjang', 'error');
    });
}

// Fungsi untuk memperbarui badge keranjang
function updateCartBadge() {
    fetch('cart_handler.php?action=get_count')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.querySelector('.cart-badge');
            if (badge) {
                badge.textContent = data.count;
            }
        }
    })
    .catch(error => {
        console.error('Error updating cart badge:', error);
    });
}

// Fungsi untuk menampilkan notifikasi
function showNotification(message, type = 'info') {
    // Hapus notifikasi sebelumnya jika ada
    const existingNotification = document.getElementById('custom-notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    const notification = document.createElement('div');
    notification.id = 'custom-notification';
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 1050; min-width: 300px;';
    
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-hide setelah 3 detik
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}

// Event listener untuk halaman yang dimuat
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Update cart badge saat halaman dimuat
    updateCartBadge();
});

// Fungsi utilitas untuk format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}