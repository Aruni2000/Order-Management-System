<!-- HTML Structure -->
<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">
                <div class="sb-sidenav-menu-heading">Core</div>
                <a class="nav-link" href="index.php" id="dashboard-link">
                    <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                    Dashboard
                </a>

                <div class="sb-sidenav-menu-heading">Interface</div>

                <!-- Orders Section -->
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseOrders"
                    aria-expanded="false" aria-controls="collapseOrders" id="orders-dropdown">
                    <div class="sb-nav-link-icon"><i class="fas fa-shopping-cart"></i></div>
                    Orders
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseOrders" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="create_order.php" id="create-order-link">Create Order</a>
                        <a class="nav-link" href="order_list.php" id="all-orders-link">All Orders</a>
                        <a class="nav-link" href="pending_order_list.php" id="pending-orders-link">Pending Orders</a>
                        <a class="nav-link" href="dispatch_order_list.php" id="dispatch-orders-link">Dispatch Orders</a>
                        <a class="nav-link" href="courier_order_list.php" id="dispatch-orders-link">Courier Orders</a>
                        <!-- <a class="nav-link" href="complete_order_list.php" id="processing-orders-link">Complete Orders </a> -->
                        <a class="nav-link" href="cancel_order_list.php" id="processing-orders-link">Cancel Orders </a>
                        <!-- <a class="nav-link" href="shipped_orders.php" id="shipped-orders-link">Shipped</a>
        <a class="nav-link" href="delivered_orders.php" id="delivered-orders-link">Delivered</a> -->
                    </nav>
                </div>


                <!-- Lead Section -->
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLeads"
                    aria-expanded="false" aria-controls="collapseLeads" id="leads-dropdown">
                    <div class="sb-nav-link-icon"><i class="fas fa-user-friends"></i></div>
                    Leads
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseLeads" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link" href="lead_upload.php" id="lead-upload-link">Lead Upload</a>
                        <a class="nav-link" href="lead_list.php" id="all-leads-link">Lead List</a>
                    </nav>
                </div>
                <!-- Users Section -->
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseUsers"
                    aria-expanded="false" aria-controls="collapseUsers" id="users-dropdown">
                    <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                    Users
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseUsers" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="users.php" id="all-users-link">
                            All Users
                        </a>
                        <a class="nav-link" href="user_logs.php" id="user-logs-link">User Activity Logs</a>

                        <a class="nav-link" href="add_user.php" id="add-user-link">Add New User</a>
                    </nav>
                </div>

                <!-- Customers Section -->
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseCustomers"
                    aria-expanded="false" aria-controls="collapseCustomers" id="customers-dropdown">
                    <div class="sb-nav-link-icon"><i class="fas fa-user-tie"></i></div>
                    Customers
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseCustomers" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="customer_list.php" id="all-customers-link">
                            All Customers
                        </a>
                        <a class="nav-link" href="add_customer.php" id="add-customer-link">Add New Customer</a>
                    </nav>
                </div>

                <!-- Products Section -->
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseProducts"
                    aria-expanded="false" aria-controls="collapseProducts" id="products-dropdown">
                    <div class="sb-nav-link-icon"><i class="fas fa-box"></i></div>
                    Products
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseProducts" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="product_list.php" id="all-products-link">
                            All Products
                        </a>
                        <a class="nav-link" href="add_product.php" id="add-product-link">Add New Product</a>
                    </nav>
                </div>

                <!-- Settings Section -->
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapsesettings"
                    aria-expanded="false" aria-controls="collapsesettings" id="products-dropdown">
                    <div class="sb-nav-link-icon"><i class="fas fa-cog fa-fw me-2"></i></div>
                    Settings
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapsesettings" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="branding.php" id="add-product-link">Edit Branding</a>
                        <a class="nav-link" href="logout.php" id="all-products-link">
                            Log Out
                        </a>
                    </nav>
                </div>

            </div>
        </div>
        <!-- <div class="sb-sidenav-footer">
            <div class="small">Logged in as:</div>
            <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Administrator'; ?>
        </div> -->
    </nav>
</div>

<!-- Separate CSS Styling -->
<style>
    /* Main sidebar styling */
    #sidenavAccordion {
        background-color: #212529;
    }

    /* Headings */
    .sb-sidenav-menu-heading {
        color: #212529;
    }

    /* Links */
    .nav-link {
        color: #ffffff;
        transition: background-color 0.3s;
    }

    /* Active link styling */
    .nav-link.active {
        background-color: #414244;
        color: white;
    }

    /* Active dropdown parent */
    .nav-link.parent-active {
        background-color: #2c3136;
    }

    /* Dropdown arrows */
    .sb-sidenav-collapse-arrow i {
        color: #212529;
    }

    /* Nested menu background */
    .sb-sidenav-menu-nested.nav {
        background-color: #212529;
    }

    /* Footer section */
    .sb-sidenav-footer {
        background-color: #0a3e57;
        color: #ffffff;
    }

    /* Footer text */
    .sb-sidenav-footer .small {
        color: #a7c7d9;
    }
</style>

<!-- Add JavaScript to handle active states -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Get current page URL
        const currentPage = window.location.pathname.split('/').pop();

        // Select all nav links
        const navLinks = document.querySelectorAll('.nav-link');

        // Loop through each link to find and mark the active one
        navLinks.forEach(link => {
            // Extract the href attribute
            const href = link.getAttribute('href');

            // Skip links with # as href (dropdown toggles)
            if (href && href !== '#') {
                const linkPage = href.split('/').pop();

                // If current page matches link's href
                if (currentPage === linkPage) {
                    // Add active class to the link
                    link.classList.add('active');

                    // If this is a nested link, expand its parent dropdown
                    const parentCollapse = link.closest('.collapse');
                    if (parentCollapse) {
                        // Show the collapse
                        const bsCollapse = new bootstrap.Collapse(parentCollapse, {
                            toggle: false
                        });
                        bsCollapse.show();

                        // Add a class to the parent dropdown toggle
                        const parentToggle = document.querySelector(`[data-bs-target="#${parentCollapse.id}"]`);
                        if (parentToggle) {
                            parentToggle.classList.add('parent-active');
                            parentToggle.classList.remove('collapsed');
                            parentToggle.setAttribute('aria-expanded', 'true');
                        }
                    }
                }
            }
        });
    });
</script>