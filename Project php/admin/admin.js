let html5QrcodeScanner;
let scannerStarted = false;
let scannerModal;

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM Content Loaded - Admin.js initialized');
    // Removed automatic scanner initialization
    loadAllOrders();
    setInterval(loadAllOrders, 10000);
    
    // Initialize modal
    const modalElement = document.getElementById('scannerModal');
    if (modalElement && typeof bootstrap !== 'undefined') {
        scannerModal = new bootstrap.Modal(modalElement);
        console.log('Scanner modal initialized successfully');
    } else {
        console.error('Bootstrap or modal element not found');
    }
});

// Make function globally accessible
window.openScannerModal = function() {
    console.log('openScannerModal called');
    if (!scannerModal) {
        console.error('Scanner modal not initialized');
        return;
    }
    scannerModal.show();
    if (!scannerStarted) {
        setTimeout(() => {
            initQRScanner();
            scannerStarted = true;
        }, 500);
    }
}

window.closeScannerModal = function() {
    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear().then(() => {
            scannerStarted = false;
        }).catch(err => {
            console.error("Error clearing scanner:", err);
        });
    }
    if (scannerModal) {
        scannerModal.hide();
    }
}

window.markAsCooked = function(securityCode) {
    if (!confirm('Mark this order as cooked?')) return;
    
    fetch('../php/admin_update_status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({security_code: securityCode, status: 'cooked'})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Order marked as cooked!');
            loadOrderByCode(securityCode);
            loadAllOrders();
        } else {
            alert(data.message || 'Update failed');
        }
    });
}

function initQRScanner() {
    html5QrcodeScanner = new Html5QrcodeScanner(
        "qr-reader",
        { fps: 10, qrbox: {width: 250, height: 250} },
        false
    );
    
    html5QrcodeScanner.render(onScanSuccess, onScanFailure);
}

function onScanSuccess(decodedText, decodedResult) {
    document.getElementById('qr-result').innerHTML = 
        '<div class="alert alert-success">Scanned: ' + decodedText + '</div>';
    loadOrderByCode(decodedText);
    
    // Stop scanner and close modal after successful scan
    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear().then(() => {
            scannerStarted = false;
            setTimeout(() => {
                closeScannerModal();
            }, 2000); // Close modal after 2 seconds
        }).catch(err => {
            console.error("Error clearing scanner:", err);
        });
    }
}

function onScanFailure(error) {
    // Silent fail
}

function loadOrderByCode(securityCode) {
    fetch('../php/admin_get_order.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({security_code: securityCode})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            displayOrderDetails(data.order);
        } else {
            document.getElementById('order-details').innerHTML = 
                '<div class="alert alert-danger">' + (data.message || 'Order not found') + '</div>';
        }
    });
}

function displayOrderDetails(order) {
    const itemsList = order.items.map(i => 
        `<li class="list-group-item">${i.item_name} (${i.option_text}) - Rs. ${i.price}</li>`
    ).join('');
    
    const statusBadge = order.status === 'cooking' ? 'warning' : 
                        order.status === 'cooked' ? 'success' : 'secondary';
    
    const html = `
        <div class="mb-3">
            <h6>Order #${order.order_group_id}</h6>
            <p>Customer: ${order.user_name}</p>
            <p>Status: <span class="badge bg-${statusBadge}">${order.status}</span></p>
            <p>Total: Rs. ${order.total_amount}</p>
            <p>Time: ${new Date(order.created_at).toLocaleString()}</p>
        </div>
        <ul class="list-group mb-3">${itemsList}</ul>
        ${order.status === 'cooking' 
            ? `<button class="btn btn-success w-100" onclick="markAsCooked('${order.security_code}')">Mark as Cooked</button>`
            : '<p class="text-muted">Order already processed</p>'}
    `;
    document.getElementById('order-details').innerHTML = html;
}

function loadAllOrders() {
    fetch('../php/admin_get_all_orders.php')
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            renderOrdersList(data.orders);
        }
    });
}

function renderOrdersList(orders) {
    if (!orders.length) {
        document.getElementById('orders-list').innerHTML = '<p class="text-muted">No orders</p>';
        return;
    }
    
    const html = `
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Time</th><th>Action</th></tr>
            </thead>
            <tbody>
                ${orders.map(o => `
                    <tr>
                        <td>${o.order_group_id}</td>
                        <td>${o.user_name}</td>
                        <td>Rs. ${o.total_amount}</td>
                        <td><span class="badge bg-${o.status==='cooking'?'warning':o.status==='cooked'?'success':'secondary'}">${o.status}</span></td>
                        <td>${new Date(o.created_at).toLocaleString()}</td>
                        <td>
                            ${o.status === 'cooking' 
                                ? `<button class="btn btn-sm btn-success" onclick="markAsCooked('${o.security_code}')">Cook</button>`
                                : '—'}
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
    document.getElementById('orders-list').innerHTML = html;
}
