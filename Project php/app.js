// SETUP (no logic changes):
// 1. Ensure php/get_orders.php and php/cancel_order.php exist (see provided files).
// 2. Confirm config.php sets $pdo correctly (host=localhost, db=myStore).
// 3. If 400 errors appear, check Network payload contains {"user_id": <int>}.
// 4. For dropdowns include bootstrap.bundle.js in each page before app.js.

document.addEventListener('DOMContentLoaded', () => {

    function getCart() {
        return JSON.parse(localStorage.getItem('foodCart')) || [];
    }
    
    function saveCart(cart) {
        localStorage.setItem('foodCart', JSON.stringify(cart));
        updateCartCount();
    }

    function updateCartCount() {
        const cartCountEl = document.getElementById('cart-count');
        if (cartCountEl) {
            cartCountEl.textContent = getCart().length;
        }
    }
    
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', (event) => {
            const btn = event.target;
            const id = btn.dataset.id;
            const name = btn.dataset.name;
            const price = parseFloat(btn.dataset.price);
            const optionsId = btn.dataset.optionsId;
            const optionsEl = document.getElementById(optionsId);
            const selectedOption = optionsEl.value;
            
            const item = {
                id: id,
                name: name,
                price: price,
                options: selectedOption
            };
            
            const cart = getCart();
            cart.push(item);
            saveCart(cart);
            
            alert(`${name} (${selectedOption}) has been added to your cart!`);
        });
    });

    // --- Add to Favorites functionality ---
    const addToFavButtons = document.querySelectorAll('.add-to-favorites');
    addToFavButtons.forEach(button => {
        button.addEventListener('click', (event) => {
            const btn = event.target;
            const id = btn.dataset.id;
            const name = btn.dataset.name;
            const user = getCurrentUser();

            if (!user) {
                alert('Please login to add favorites');
                window.location.href = 'login.php';
                return;
            }

            fetch('php/add_favorite.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ user_id: user.id, item_id: id })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    btn.innerHTML = '♥ Favorited';
                    btn.classList.remove('btn-outline-danger');
                    btn.classList.add('btn-danger');
                    btn.disabled = true;
                    showPopup(`${name} added to favorites!`, 'success');
                } else {
                    showPopup(data.message || 'Failed to add favorite', 'error');
                }
            })
            .catch(() => showPopup('Network error', 'error'));
        });
    });

    const cartItemsContainer = document.getElementById('cart-items-container');
    const totalPriceEl = document.getElementById('total-price');
    const checkoutBtn = document.getElementById('checkout-btn');
    const ordersContainer = document.getElementById('user-orders-container');
    if (!ordersContainer && window.location.pathname.toLowerCase().includes('orders')) {
        console.warn('[orders.html] Expected <div id="user-orders-container"></div> (line 38) not found. Add: <div id="user-orders-container"></div>');
    }

    if (cartItemsContainer) {
        loadCartItems();
    }

    function loadCartItems() {
        const cart = getCart();
        cartItemsContainer.innerHTML = '';
        let total = 0;

        if (cart.length === 0) {
            cartItemsContainer.innerHTML = '<p class="text-center">Your cart is empty.</p>';
            if(checkoutBtn) checkoutBtn.disabled = true;
            return;
        }

        cart.forEach((item, idx) => {
            total += item.price;
            const itemHTML = `
                <div class="list-group-item d-flex justify-content-between align-items-center" data-index="${idx}">
                    <div>
                        <h5 class="mb-1">${item.name}</h5>
                        <small class="text-muted">Options: ${item.options}</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold">Rs. ${item.price}</span>
                        <button class="btn btn-sm btn-outline-danger remove-item" data-index="${idx}">Remove</button>
                    </div>
                </div>
            `;
            cartItemsContainer.innerHTML += itemHTML;
        });

        totalPriceEl.textContent = `Rs. ${total}`;
    }

    function removeCartItem(index) {
        const cart = getCart();
        if (index < 0 || index >= cart.length) return;
        cart.splice(index, 1);
        saveCart(cart);
        loadCartItems();
    }

    if (cartItemsContainer) {
        cartItemsContainer.addEventListener('click', (e) => {
            const btn = e.target.closest('.remove-item');
            if (!btn) return;
            const idx = parseInt(btn.dataset.index, 10);
            removeCartItem(idx);
        });
    }

    // --- Added config & helpers for popup + active order cancel window ---
    const CANCEL_WINDOW_MS = 5 * 60 * 1000;

    function showPopup(message, type = 'info', timeout = 3000) {
        const popup = document.createElement('div');
        popup.textContent = message;
        popup.style.position = 'fixed';
        popup.style.top = '20px';
        popup.style.right = '20px';
        popup.style.zIndex = '9999';
        popup.style.padding = '12px 18px';
        popup.style.borderRadius = '6px';
        popup.style.fontFamily = 'system-ui, Arial';
        popup.style.boxShadow = '0 2px 8px rgba(0,0,0,.15)';
        popup.style.background = type === 'success' ? '#28a745' :
                                 type === 'error'   ? '#dc3545' :
                                 '#0d6efd';
        popup.style.color = '#fff';
        document.body.appendChild(popup);
        setTimeout(() => popup.remove(), timeout);
    }

    function showQRCodePopup(securityCode, orderId) {
        const overlay = document.createElement('div');
        overlay.style.position = 'fixed';
        overlay.style.top = '0';
        overlay.style.left = '0';
        overlay.style.width = '100%';
        overlay.style.height = '100%';
        overlay.style.background = 'rgba(0,0,0,0.7)';
        overlay.style.zIndex = '10000';
        overlay.style.display = 'flex';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'center';

        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(securityCode)}`;
        
        overlay.innerHTML = `
            <div style="background:white; padding:30px; border-radius:12px; text-align:center; max-width:400px;">
                <h4 style="margin-bottom:20px; color:#333;">Order Confirmation</h4>
                <p style="color:#666; margin-bottom:20px;">Order #${orderId}</p>
                <img src="${qrUrl}" alt="QR Code" style="width:300px; height:300px; margin:0 auto; display:block;">
                <p style="color:#e63946; margin-top:20px; font-size:14px;">⏱ You can cancel this order within 5 minutes</p>
                <button id="close-qr-popup" style="margin-top:20px; padding:10px 30px; background:#007bff; color:white; border:none; border-radius:6px; cursor:pointer; font-size:16px;">Close</button>
            </div>
        `;

        document.body.appendChild(overlay);
        
        document.getElementById('close-qr-popup').addEventListener('click', () => {
            overlay.remove();
        });
        
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) overlay.remove();
        });
    }

    function getActiveOrder() {
        return JSON.parse(localStorage.getItem('activeOrder')) || null;
    }
    function setActiveOrder(order) {
        if (order) localStorage.setItem('activeOrder', JSON.stringify(order));
        else localStorage.removeItem('activeOrder');
    }

    function renderCancelButton() {
        const existing = document.getElementById('cancel-order-btn');
        const active = getActiveOrder();
        if (!active) {
            if (existing) existing.remove();
            return;
        }
        const expiresAt = active.placedAt + CANCEL_WINDOW_MS;
        if (Date.now() > expiresAt) {
            setActiveOrder(null);
            if (existing) existing.remove();
            return;
        }
        if (!existing) {
            const btn = document.createElement('button');
            btn.id = 'cancel-order-btn';
            btn.textContent = 'Cancel Order';
            btn.style.position = 'fixed';
            btn.style.bottom = '20px';
            btn.style.right = '20px';
            btn.style.zIndex = '9999';
            btn.className = 'btn btn-warning';
            btn.addEventListener('click', attemptCancel);
            document.body.appendChild(btn);
        } else {
            existing.disabled = false;
        }
    }

    function attemptCancel() {
        const active = getActiveOrder();
        if (!active) return;
        const age = Date.now() - active.placedAt;
        if (age > CANCEL_WINDOW_MS) {
            showPopup('Cancel window expired.', 'error');
            setActiveOrder(null);
            renderCancelButton();
            return;
        }
        fetch('php/cancel_order.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ orderId: active.orderId, securityCode: active.securityCode })
        })
        .then(r => r.json().catch(()=>({success:false,message:'Bad JSON'}))) // FIX: was ".then data =>"
        .then(data => {                                                  // FIX: was ".then data =>"
            if (data.success) {
                showPopup('Order canceled.', 'success');
                setActiveOrder(null);
                renderCancelButton();
            } else {
                showPopup(data.message || 'Cancel failed.', 'error');
            }
        })
        .catch(() => showPopup('Cancel request failed.', 'error'));
    }

    function initActiveOrderUI() {
        renderCancelButton();
        setInterval(renderCancelButton, 15000); // periodic cleanup
    }

    // --- User management (signup / logout) ---
    function getCurrentUser() {
        return JSON.parse(localStorage.getItem('currentUser')) || null;
    }
    function setCurrentUser(u) {
        if (u) localStorage.setItem('currentUser', JSON.stringify(u));
        else localStorage.removeItem('currentUser');
        renderUserUI();
    }

    // The navbar code is inside this function
    function renderUserUI() {
        const user = getCurrentUser();
        const nav = document.querySelector('.navbar');
        const navContainer = nav ? nav.querySelector('.container-fluid') : null;

        if (!nav || !navContainer) return;

        // --- 1. Cleanup Old UI Elements ---
        ['sidebar-nav', 'sidebar-toggle-btn', 'account-bar', 'brand-center-style', 'navbarNav'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.remove();
        });
        // Also remove any existing buttons to prevent duplication
        navContainer.querySelectorAll('.navbar-nav, .navbar-toggler, .collapse').forEach(el => el.remove());
        document.body.style.marginLeft = ''; // Reset body margin

        // --- 2. Style The Navbar ---
        nav.className = 'navbar navbar-expand-lg navbar-light bg-light shadow-sm';
        navContainer.style.justifyContent = ''; // Reset flex centering

        // --- 3. Create Navbar Structure ---
        // Toggler for mobile
        const toggler = document.createElement('button');
        toggler.className = 'navbar-toggler';
        toggler.type = 'button';
        toggler.setAttribute('data-bs-toggle', 'collapse');
        toggler.setAttribute('data-bs-target', '#navbarNav');
        toggler.innerHTML = '<span class="navbar-toggler-icon"></span>';

        // Collapsible container for links
        const collapseDiv = document.createElement('div');
        collapseDiv.className = 'collapse navbar-collapse';
        collapseDiv.id = 'navbarNav';

        // Unordered list for nav items
        const ul = document.createElement('ul');
        ul.className = 'navbar-nav ms-auto align-items-center';

        collapseDiv.appendChild(ul);
        navContainer.appendChild(toggler);
        navContainer.appendChild(collapseDiv);

        // --- 4. Build and Inject Links ---
        let links = [];
        if (user) {
            links.push(`<li class="nav-item"><span id="user-name-badge" class="nav-link">Welcome, <strong>${user.name}</strong></span></li>`);
        }
        links.push(`<li class="nav-item"><a class="nav-link" href="admin/login.php">Admin</a></li>`);
        links.push(`<li class="nav-item"><a class="nav-link" href="orders.html">My Orders</a></li>`);

        if (user) {
            links.push(`<li class="nav-item"><button id="logout-btn" class="btn btn-link nav-link text-danger">Logout</button></li>`);
        } else {
            links.push(`<li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>`);
            links.push(`<li class="nav-item"><a class="nav-link" href="signup.php">Sign Up</a></li>`);
        }
        links.push(`<li class="nav-item ms-lg-2"><a class="btn btn-warning" href="cart.html">Cart (<span id="cart-count">0</span>)</a></li>`);
        
        ul.innerHTML = links.join('');
        updateCartCount();
    }

    document.addEventListener('click', e => {
        if (e.target.id === 'logout-btn') { setCurrentUser(null); showPopup('Logged out','info'); }
    });

    renderUserUI();

    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', () => {
            const user = getCurrentUser();
            if (!user) {
                window.location.href = 'signup.php';
                return;
            }
            const isConfirmed = confirm('Are you sure you want to confirm this order?');
            
            if (isConfirmed) {
                const cart = getCart();
                const total = cart.reduce((sum, item) => sum + item.price, 0);
                
                const orderData = {
                    items: cart,
                    totalAmount: total,
                    securityCode: generateSecurityCode(),
                    user_id: user.id
                };
                
                console.log('--- Data format to be sent to PHP (as JSON) ---');
                console.log(JSON.stringify(orderData, null, 2));

                fetch('php/checkout.php', {
                  method:'POST',
                  headers:{'Content-Type':'application/json'},
                  body: JSON.stringify(orderData)
                })
                .then(r => {
                  const ct = r.headers.get('content-type') || '';
                  if (!ct.includes('application/json')) {
                    return r.text().then(t => { throw new Error('Non-JSON response: ' + t.slice(0, 120)); });
                  }
                  if (r.status === 400) {
                    return r.text().then(t => { throw new Error('400 Bad Request: ' + t.slice(0,120)); });
                  }
                  return r.json();
                })
                .then(data => {
                  if (data.success) {
                    localStorage.removeItem('foodCart');
                    if (cartItemsContainer) {
                        loadCartItems();
                    }
                    updateCartCount();
                    
                    const orderId = data.order_group_id || data.orderId || orderData.securityCode;
                    setActiveOrder({
                        orderId: orderId,
                        placedAt: Date.now(),
                        securityCode: data.security_code || orderData.securityCode
                    });
                    renderCancelButton();
                    showPopup('Order placed successfully!', 'success');
                    
                    // Show QR code popup
                    showQRCodePopup(data.security_code || orderData.securityCode, orderId);
                  } else {
                    showPopup('Error placing order. Please try again.', 'error');
                  }
                })
                .catch(err => {
                    console.error('Checkout failed:', err);
                    showPopup('Checkout failed.', 'error');
                });
            }
        });
    }

    function generateSecurityCode() {
        const randomDigits = Math.floor(100000000000 + Math.random() * 900000000000);
        return `mk${randomDigits}`;
    }

    initActiveOrderUI();
    updateCartCount();

    // --- Orders section: fetch & render user orders, handle cancellations ---
    function fetchAndRenderOrders() {
        const user = getCurrentUser();
        if (!ordersContainer) return;
        if (!user) {
            ordersContainer.innerHTML = '<div class="alert alert-warning">Please <a href="login.php">login</a> to view orders.</div>';
            return;
        }
        const uid = parseInt(user.id, 10);
        if (!uid) {
            ordersContainer.innerHTML = '<div class="alert alert-danger">Invalid user session.</div>';
            return;
        }
        ordersContainer.innerHTML = '<p class="text-muted">Loading...</p>';

        fetch('php/get_orders.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ user_id: uid, flat: true })
        })
        .then(r => {
            if (r.status === 400) return r.text().then(t => { throw new Error(t); });
            if (!r.ok) throw new Error('HTTP '+r.status);
            return r.text();
        })
        .then(txt => {
            if (!txt.trim()) throw new Error('Empty response');
            const data = JSON.parse(txt);
            if (!data.success) {
                ordersContainer.innerHTML = '<div class="alert alert-danger">'+(data.message||'Error loading orders')+'</div>';
                return;
            }
            if (!data.orders.length) {
                ordersContainer.innerHTML = '<p class="text-muted">No orders yet.</p>';
                return;
            }
            let html = `
              <div class="table-responsive">
                <table class="table table-bordered table-hover">
                  <thead class="table-dark">
                    <tr>
                      <th>Order #</th>
                      <th>Security Code</th>
                      <th>Item</th>
                      <th>Options</th>
                      <th>Price</th>
                      <th>Status</th>
                      <th>Ordered At</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>`;
            const now = Date.now();
            data.orders.forEach(row => {
                const ageMs = now - new Date(row.item_ordered_at).getTime();
                const canCancel = row.status === 'cooking' && ageMs <= 5*60*1000;
                const minutesLeft = canCancel ? Math.ceil((5*60*1000 - ageMs) / 60000) : 0;
                const isCanceled = row.status === 'canceled';
                
                html += `
                  <tr>
                    <td>${row.order_group_id}</td>
                    <td>
                        <button class="btn btn-xs btn-outline-primary show-qr-btn" data-code="${row.security_code}" data-order="${row.order_group_id}">
                            <small>Show QR</small>
                        </button>
                    </td>
                    <td>${row.item_name}</td>
                    <td><small>${row.option_text || 'Regular'}</small></td>
                    <td class="fw-bold">Rs. ${row.price}</td>
                    <td><span class="badge bg-${row.status==='cooking'?'warning':row.status==='cooked'?'success':'secondary'}">${row.status}</span></td>
                    <td><small>${new Date(row.item_ordered_at).toLocaleString()}</small></td>
                    <td>
                      ${canCancel
                        ? `<button class="btn btn-sm btn-danger cancel-order-btn" data-code="${row.security_code}">Cancel (${minutesLeft}min)</button>`
                        : isCanceled
                        ? `<button class="btn btn-sm btn-outline-danger delete-order-btn" data-code="${row.security_code}">Delete</button>`
                        : '<span class="text-muted">—</span>'}
                    </td>
                  </tr>`;
            });
            html += '</tbody></table></div>';
            ordersContainer.innerHTML = html;
        })
        .catch(err => {
            ordersContainer.innerHTML = '<div class="alert alert-danger">Failed: '+err.message+'</div>';
        });
    }

    if (ordersContainer) {
        fetchAndRenderOrders();
        ordersContainer.addEventListener('click', e => {
            // Show QR code button
            if (e.target.closest('.show-qr-btn')) {
                const btn = e.target.closest('.show-qr-btn');
                const code = btn.dataset.code;
                const orderId = btn.dataset.order;
                showQRCodePopup(code, orderId);
                return;
            }
            
            // Delete canceled order button
            if (e.target.closest('.delete-order-btn')) {
                const btn = e.target.closest('.delete-order-btn');
                const code = btn.dataset.code;
                const user = getCurrentUser();
                if (!user) { window.location.href = 'signup.php'; return; }
                
                if (!confirm('Are you sure you want to delete this canceled order from your history?')) return;
                
                btn.disabled = true;
                fetch('php/delete_order.php', {
                    method:'POST',
                    headers:{'Content-Type':'application/json'},
                    body: JSON.stringify({ security_code: code, user_id: user.id })
                })
                .then(r => r.json().catch(()=>({success:false,message:'Bad JSON'})))
                .then(d => {
                    if (d.success) {
                        showPopup('Order deleted','success');
                        fetchAndRenderOrders();
                    } else {
                        showPopup(d.message || 'Delete failed','error');
                        btn.disabled = false;
                    }
                })
                .catch(()=> { showPopup('Delete failed','error'); btn.disabled = false; });
                return;
            }
            
            // Cancel order button
            const btn = e.target.closest('.cancel-order-btn');
            if (!btn) return;
            const code = btn.dataset.code;
            const user = getCurrentUser();
            if (!user) { window.location.href = 'signup.php'; return; }
            
            if (!confirm('Are you sure you want to cancel this order?')) return;
            
            btn.disabled = true;
            fetch('php/cancel_order.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ security_code: code, user_id: user.id })
            })
            .then(r => r.json().catch(()=>({success:false,message:'Bad JSON'}))) // FIXED: parentheses around r
            .then(d => {
                if (d.success) {
                    showPopup('Order canceled','success');
                    fetchAndRenderOrders();
                } else {
                    showPopup(d.message || 'Cancel failed','error');
                    btn.disabled = false;
                }
            })
            .catch(()=> { showPopup('Cancel failed','error'); btn.disabled = false; });
        });
        
        setInterval(fetchAndRenderOrders, 20000);
    }
});