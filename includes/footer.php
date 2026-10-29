<?php
declare(strict_types=1);

/**
 * Global Footer Partial
 * Expense Behaviour Analytical Platform (EBAP)
 */

$isAppPage = $isAppPage ?? isLoggedIn();
?>

<?php if ($isAppPage): ?>
        </div> <!-- /.content-wrapper -->

        <!-- Application Footer -->
        <footer class="mt-auto py-3 px-4 bg-white border-top text-center text-md-start">
            <div class="container-fluid px-0">
                <div class="row align-items-center">
                    <div class="col-md-6 mb-2 mb-md-0">
                        <small class="text-muted">
                            &copy; <?= date('Y') ?> <strong><?= e(APP_NAME) ?></strong>. Built for BCA Academic Evaluation & Personal Analytics.
                        </small>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <small class="text-muted">
                            <span class="badge bg-light text-secondary border me-2">Educational Tool</span>
                            <span>Not regulated financial advice.</span>
                        </small>
                    </div>
                </div>
            </div>
        </footer>
    </div> <!-- /#ebap-main -->
</div> <!-- /#ebap-layout -->
<?php endif; ?>

<!-- Bootstrap 5.3 Bundle JS with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom EBAP Client JS -->
<script src="<?= url('assets/js/app.js') ?>"></script>
<script src="<?= url('assets/js/charts.js') ?>"></script>

</body>
</html>
