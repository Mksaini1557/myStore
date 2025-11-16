document.addEventListener('DOMContentLoaded', () => {
    loadUserOrders();
    setInterval(loadUserOrders, 10000); // Refresh every 10 seconds
});

function loadUserOrders() {
    const userId = localStorage.getItem('user_id');
    if (!userId) {
        document.getElementById('orders-list').innerHTML = 
            '<div class="alert alert-warning">Please log in to view orders</div>';
        return;
    }

    fetch('php/get_user_orders.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({user_id: userId})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            displayOrders(data.orders);
        }
    });
}

function displayOrders(orders) {
    if (!orders.length) {
        document.getElementById('orders-list').innerHTML = 
            '<div class="alert alert-info">No orders yet</div>';
        return;
    }

    const html = orders.map(order => {
        const statusBadge = getStatusBadge(order.status);
        const itemsList = order.items.map(item => 
            `<li class="list-group-item">
                ${item.item_name} (${item.option_text}) - Rs. ${item.price}
            </li>`
        ).join('');

        return `
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Order #${order.order_group_id}</span>
                    ${statusBadge}
                </div>
                <div class="card-body">
                    <ul class="list-group mb-3">${itemsList}</ul>
                    <p class="mb-1"><strong>Total:</strong> Rs. ${order.total_amount}</p>
                    <p class="mb-1"><strong>Ordered:</strong> ${new Date(order.created_at).toLocaleString()}</p>
                    ${order.status === 'cooked' ? `
                        <div class="mt-3">
                            <p class="text-muted mb-2">Your QR Code (Show this to collect your order):</p>
                            <div id="qr-${order.security_code}"></div>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }).join('');

    document.getElementById('orders-list').innerHTML = html;

    // Generate QR codes for cooked orders
    orders.forEach(order => {
        if (order.status === 'cooked') {
            new QRCode(document.getElementById(`qr-${order.security_code}`), {
                text: order.security_code,
                width: 200,
                height: 200
            });
        }
    });
}

function getStatusBadge(status) {
    switch(status) {
        case 'cooking':
            return '<span class="badge bg-warning text-dark">Cooking</span>';
        case 'cooked':
            return '<span class="badge bg-success">Ready for Pickup</span>';
        case 'delivered':
            return '<span class="badge bg-primary">Delivered</span>';
        default:
            return '<span class="badge bg-secondary">Pending</span>';
    }
}
