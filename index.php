<?php
declare(strict_types=1);

/**
 * Public Landing Page
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect authenticated users directly to their personal analytics dashboard
if (isLoggedIn()) {
    header('Location: ' . url('dashboard/index.php'));
    exit;
}

$pageTitle = 'Home - Understand & Improve Your Spending Habits';
$isAppPage = false;
require_once __DIR__ . '/includes/header.php';
?>

<!-- Public Navigation Header -->
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top py-3">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-dark" href="<?= url('index.php') ?>">
            <div class="rounded-3 bg-primary text-white d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                <i class="bi bi-graph-up-arrow fs-5"></i>
            </div>
            <span>EBAP</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="publicNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-2 mt-3 mt-lg-0">
                <li class="nav-item"><a class="nav-link text-secondary fw-medium px-3" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link text-secondary fw-medium px-3" href="#workflow">How It Works</a></li>
                <li class="nav-item"><a class="nav-link text-secondary fw-medium px-3" href="#preview">Preview</a></li>
                <li class="nav-item"><a class="nav-link text-secondary fw-medium px-3" href="#faq">FAQ</a></li>
                <li class="nav-item ms-lg-2">
                    <a class="btn btn-ebap-outline btn-sm px-3 py-1.5" href="<?= url('login.php') ?>">Sign In</a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-ebap btn-sm px-3 py-1.5" href="<?= url('register.php') ?>">Get Started Free</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="py-5 bg-white border-bottom">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill bg-light border text-primary mb-3" style="font-size: 0.85rem; font-weight: 500;">
                    <i class="bi bi-stars"></i> Intelligent Personal Financial Analysis
                </div>
                <h1 class="display-5 fw-bold text-dark lh-sm mb-3">
                    Don't just count your expenses. <br>
                    <span class="text-primary">Understand your spending behaviour.</span>
                </h1>
                <p class="lead text-secondary mb-4 fs-6">
                    Many people know how much money they spend, but few understand where it goes, which categories dominate, how habits shift over time, or which anomalies threaten financial goals. EBAP transforms daily records into actionable, explainable behavioural insights.
                </p>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <a href="<?= url('register.php') ?>" class="btn btn-ebap btn-lg px-4 py-2.5 shadow-sm">
                        <i class="bi bi-person-plus me-2"></i> Create Free Account
                    </a>
                    <a href="<?= url('login.php?demo=1') ?>" class="btn btn-ebap-outline btn-lg px-4 py-2.5">
                        <i class="bi bi-play-circle me-2 text-primary"></i> Live Demo Sign In
                    </a>
                </div>
                <div class="d-flex align-items-center gap-4 mt-4 pt-2 text-muted" style="font-size: 0.8rem;">
                    <span><i class="bi bi-shield-check text-success me-1.5"></i> 100% Private Data</span>
                    <span><i class="bi bi-check2-circle text-success me-1.5"></i> No Bank Logins Required</span>
                    <span><i class="bi bi-code-slash text-primary me-1.5"></i> Academic Submission Ready</span>
                </div>
            </div>
            <div class="col-lg-5">
                <!-- Interactive Sample Preview Card -->
                <div class="ebap-card p-4 border bg-light shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1">Sample Analytics Preview</span>
                        <small class="text-muted">October 2026</small>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="bg-white p-2.5 rounded-3 border">
                                <small class="text-muted d-block" style="font-size: 0.72rem;">NET CASH FLOW</small>
                                <span class="fw-bold text-success fs-6">+₹24,561.00</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-white p-2.5 rounded-3 border">
                                <small class="text-muted d-block" style="font-size: 0.72rem;">HIGHEST SPENDING</small>
                                <span class="fw-bold text-dark fs-6">Housing (34%)</span>
                            </div>
                        </div>
                    </div>
                    <div style="height: 180px;">
                        <canvas id="heroSampleChart"></canvas>
                    </div>
                    <div class="mt-3 p-2.5 bg-white rounded-3 border" style="font-size: 0.8rem;">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-lightbulb-fill text-warning mt-0.5"></i>
                            <div>
                                <strong>Behavioural Insight:</strong>
                                <span class="text-secondary d-block">"Weekend spending represents 42% of your discretionary expenses despite being only 2 out of 7 days."</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Grid -->
<section id="features" class="py-5 bg-light">
    <div class="container py-4">
        <div class="text-center max-w-xl mx-auto mb-5" style="max-width: 650px;">
            <h6 class="text-primary fw-bold text-uppercase tracking-wide mb-2" style="font-size: 0.8rem;">Platform Modules</h6>
            <h2 class="fw-bold text-dark">Engineered for Deep Financial Understanding</h2>
            <p class="text-secondary fs-6">Every component is built with deterministic business logic and transparent calculations derived directly from your records.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="ebap-card p-4 h-100 bg-white">
                    <div class="stat-icon bg-primary-subtle text-primary mb-3">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <h5 class="fw-bold text-dark fs-6 mb-2">Income & Expense Tracking</h5>
                    <p class="text-secondary mb-0" style="font-size: 0.875rem;">
                        Comprehensive CRUD with categories, payment methods, merchant tagging, notes, and strict decimal precision.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="ebap-card p-4 h-100 bg-white">
                    <div class="stat-icon bg-success-subtle text-success mb-3">
                        <i class="bi bi-pie-chart"></i>
                    </div>
                    <h5 class="fw-bold text-dark fs-6 mb-2">Behavioural Analytics</h5>
                    <p class="text-secondary mb-0" style="font-size: 0.875rem;">
                        Identifies dominant categories, essential vs discretionary ratios, weekday vs weekend clustering, and mean-to-median skew.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="ebap-card p-4 h-100 bg-white">
                    <div class="stat-icon bg-warning-subtle text-warning mb-3">
                        <i class="bi bi-shield-exclamation"></i>
                    </div>
                    <h5 class="fw-bold text-dark fs-6 mb-2">Spending Anomaly Detection</h5>
                    <p class="text-secondary mb-0" style="font-size: 0.875rem;">
                        Transparent rule-based detection flagging transactions exceeding category medians, daily spending spikes, or duplicates.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="ebap-card p-4 h-100 bg-white">
                    <div class="stat-icon bg-info-subtle text-info mb-3">
                        <i class="bi bi-calculator"></i>
                    </div>
                    <h5 class="fw-bold text-dark fs-6 mb-2">Budget Monitoring & Warnings</h5>
                    <p class="text-secondary mb-0" style="font-size: 0.875rem;">
                        Set overall or category-specific limits. Real-time utilization tracking with warning thresholds at 70%, 85%, and 100%.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="ebap-card p-4 h-100 bg-white">
                    <div class="stat-icon bg-danger-subtle text-danger mb-3">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>
                    <h5 class="fw-bold text-dark fs-6 mb-2">Recurring & Subscriptions</h5>
                    <p class="text-secondary mb-0" style="font-size: 0.875rem;">
                        Normalize weekly, monthly, and annual commitments into standard monthly costs. One-click logging for paid bills.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="ebap-card p-4 h-100 bg-white">
                    <div class="stat-icon bg-secondary-subtle text-secondary mb-3">
                        <i class="bi bi-file-earmark-spreadsheet"></i>
                    </div>
                    <h5 class="fw-bold text-dark fs-6 mb-2">Reports & CSV Import/Export</h5>
                    <p class="text-secondary mb-0" style="font-size: 0.875rem;">
                        Export reports with CSV injection protection. Easily import historical bank statements through CSV with row-level validation.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works (8-Step Workflow) -->
<section id="workflow" class="py-5 bg-white border-top border-bottom">
    <div class="container py-4">
        <div class="text-center max-w-xl mx-auto mb-5" style="max-width: 650px;">
            <h6 class="text-primary fw-bold text-uppercase tracking-wide mb-2" style="font-size: 0.8rem;">Structured Workflow</h6>
            <h2 class="fw-bold text-dark">How The Platform Works</h2>
            <p class="text-secondary fs-6">From initial setup to ongoing financial mindfulness in eight clear steps.</p>
        </div>

        <div class="row g-4">
            <?php
            $steps = [
                ['num' => '01', 'title' => 'Register & Setup', 'desc' => 'Create a private local account and set your preferred currency (INR ₹, USD $, EUR €).'],
                ['num' => '02', 'title' => 'Add Transactions', 'desc' => 'Record daily income and expenses or bulk-import via CSV templates.'],
                ['num' => '03', 'title' => 'Categorize Spending', 'desc' => 'Classify items into essential needs (groceries, rent) vs discretionary choices.'],
                ['num' => '04', 'title' => 'Set Budgets', 'desc' => 'Establish monthly overall limits and category caps with automated alert thresholds.'],
                ['num' => '05', 'title' => 'Track Visual Trends', 'desc' => 'Explore daily line graphs, monthly trends, and payment method distribution charts.'],
                ['num' => '06', 'title' => 'Review Behaviour Insights', 'desc' => 'Receive explainable natural language insights computed from actual transaction math.'],
                ['num' => '07', 'title' => 'Detect Anomalies', 'desc' => 'Review statistical outlier charges and unexpected surges against historical baselines.'],
                ['num' => '08', 'title' => 'Export & Plan Goals', 'desc' => 'Download sanitized CSV/print reports and track progress towards financial targets.']
            ];
            foreach ($steps as $s):
            ?>
            <div class="col-md-6 col-lg-3">
                <div class="p-3.5 border rounded-3 bg-light h-100">
                    <span class="badge bg-primary text-white mb-2 font-monospace fs-7"><?= $s['num'] ?></span>
                    <h6 class="fw-bold text-dark mb-1.5"><?= $s['title'] ?></h6>
                    <p class="text-secondary mb-0" style="font-size: 0.825rem;"><?= $s['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Privacy & Security Guarantee -->
<section class="py-5 bg-light">
    <div class="container py-3">
        <div class="ebap-card p-5 bg-white border">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center gap-2 text-success mb-2">
                        <i class="bi bi-shield-lock-fill fs-4"></i>
                        <h4 class="fw-bold mb-0 text-dark">Commitment to User Privacy & Security</h4>
                    </div>
                    <p class="text-secondary mb-3 fs-6">
                        Personal financial records belong exclusively to you. EBAP requires no bank account numbers, no debit/credit card credentials, and no external banking passwords. All authentication utilizes PHP bcrypt hashing with PDO prepared statements.
                    </p>
                    <ul class="list-unstyled d-flex flex-wrap gap-4 text-muted mb-0" style="font-size: 0.85rem;">
                        <li><i class="bi bi-check-circle-fill text-success me-1"></i> CSRF Attack Protection</li>
                        <li><i class="bi bi-check-circle-fill text-success me-1"></i> SQL Injection Immunity via PDO</li>
                        <li><i class="bi bi-check-circle-fill text-success me-1"></i> Strict Multi-User Data Isolation</li>
                        <li><i class="bi bi-check-circle-fill text-success me-1"></i> Formula Injection Sanitized CSV</li>
                    </ul>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="<?= url('register.php') ?>" class="btn btn-ebap btn-lg px-4 py-2.5">
                        Get Started Now
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section id="faq" class="py-5 bg-white border-top">
    <div class="container py-4" style="max-width: 800px;">
        <div class="text-center mb-5">
            <h6 class="text-primary fw-bold text-uppercase tracking-wide mb-2" style="font-size: 0.8rem;">Frequently Asked Questions</h6>
            <h2 class="fw-bold text-dark">Have Questions?</h2>
        </div>

        <div class="accordion shadow-sm" id="faqAccordion">
            <div class="accordion-item border-0 mb-2 rounded-3 overflow-hidden border">
                <h2 class="accordion-header" id="faq1">
                    <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                        Is EBAP suitable for academic evaluation and BCA project viva?
                    </button>
                </h2>
                <div id="collapse1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-secondary fs-7">
                        Yes! The platform is architected specifically with normalized MySQL tables, Core PHP 8, PDO prepared statements, explainable statistical formulas, clear documentation, and seed data for live demonstration.
                    </div>
                </div>
            </div>
            <div class="accordion-item border-0 mb-2 rounded-3 overflow-hidden border">
                <h2 class="accordion-header" id="faq2">
                    <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                        Does the system claim to predict fraud or provide financial advice?
                    </button>
                </h2>
                <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-secondary fs-7">
                        No. EBAP is an educational and personal financial analytics tool. Spending anomalies flag statistical deviations from your own historical baseline for user review; it does not claim to detect banking fraud or offer regulated financial advice.
                    </div>
                </div>
            </div>
            <div class="accordion-item border-0 mb-2 rounded-3 overflow-hidden border">
                <h2 class="accordion-header" id="faq3">
                    <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                        How does the platform handle empty databases?
                    </button>
                </h2>
                <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-secondary fs-7">
                        When a newly registered user logs in with zero transactions, EBAP gracefully shows helpful onboarding prompts and zeros rather than dividing by zero, crashing, or fabricating fake statistics.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Chart Init Script for Landing Page Hero Preview -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('heroSampleChart');
    if (ctx && typeof Chart !== 'undefined') {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Housing & Utilities', 'Food & Groceries', 'Restaurants', 'Shopping', 'Subscriptions'],
                datasets: [{
                    data: [18000, 8550, 7050, 5400, 2449],
                    backgroundColor: ['#3b82f6', '#10b981', '#f97316', '#ec4899', '#06b6d4'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
