<?php
$currentYear = date('Y');
?>
<footer class="footer mt-auto py-3 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-md-6 text-center text-md-start">
                <span class="text-muted">&copy; <?php echo $currentYear; ?> Martin Wickramasinghe Trust. All rights reserved.</span>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <span class="text-muted">Leave Management System</span>
            </div>
        </div>
    </div>
</footer>

<style>
.footer {
    position: fixed;
    bottom: 0;
    width: 100%;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 1rem 0;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
}

.footer .text-muted {
    color: rgba(255, 255, 255, 0.8) !important;
}

.footer a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer a:hover {
    color: white;
}
</style> 