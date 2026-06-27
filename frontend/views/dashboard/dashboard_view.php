<?php
declare(strict_types=1);

if (!defined('MNS_CONTROLLER_RENDER')) {
    require_once __DIR__ . '/../../../backend/controllers/dashboard_controller.php';
    handleDashboardRequest();
    return;
}

require_once __DIR__ . '/../../../backend/includes/helpers.php';

$flash = getFlashMessage();
$user = $user ?? currentUser();
$role = $role ?? ($user['role'] ?? '');
$cards = $dashboard['cards'] ?? [];
$monthlyRevenue = $dashboard['monthlyRevenue'] ?? [];
$missionStatuses = $dashboard['missionStatuses'] ?? [];
$occupation = $dashboard['occupation'] ?? [];
$money = static fn(float $value): string => number_format($value, 0, ',', ' ') . ' FCFA';

require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="container-fluid py-4">
        <?php if ($flash !== null): ?>
            <div class="alert alert-<?php echo e($flash['type'] === 'success' ? 'success' : 'danger'); ?>">
                <?php echo e((string) $flash['message']); ?>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Tableau de bord</h1>
                <p class="text-muted mb-0"><?php echo e((string) ($user['full_name'] ?? 'Utilisateur')); ?> - <?php echo e((string) $role); ?></p>
            </div>
            <a href="/MNS_CORPORATE/logout.php" class="btn btn-outline-secondary">Deconnexion</a>
        </div>

        <?php if ($role === 'EXPERT'): ?>
            <div class="row g-3 mb-4">
                <?php
                $cardLabels = [
                    'active_clients' => ['Clients actifs', (string) ($cards['active_clients'] ?? 0)],
                    'active_missions' => ['Missions en cours', (string) ($cards['active_missions'] ?? 0)],
                    'overdue_deadlines' => ['Echeances en retard', (string) ($cards['overdue_deadlines'] ?? 0)],
                    'unpaid_invoices' => ['Factures impayees', $money((float) ($cards['unpaid_invoices'] ?? 0))],
                    'monthly_revenue' => ['CA encaisse ce mois', $money((float) ($cards['monthly_revenue'] ?? 0))],
                    'new_documents' => ['Documents nouveaux', (string) ($cards['new_documents'] ?? 0)],
                    'pending_timesheets' => ['Timesheets a valider', (string) ($cards['pending_timesheets'] ?? 0)],
                ];
                ?>
                <?php foreach ($cardLabels as $card): ?>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <div class="text-muted small"><?php echo e($card[0]); ?></div>
                                <div class="fs-4 fw-semibold"><?php echo e($card[1]); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-12 col-xl-5">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h2 class="h6">CA encaisse des 6 derniers mois</h2>
                            <?php if ($monthlyRevenue === []): ?>
                                <p class="text-muted mb-0">Aucun paiement enregistre.</p>
                            <?php else: ?>
                                <canvas id="revenueChart" height="160"></canvas>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h2 class="h6">Missions par statut</h2>
                            <canvas id="missionsChart" height="180"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h2 class="h6">Taux d'occupation</h2>
                            <?php if ($occupation === []): ?>
                                <p class="text-muted mb-0">Aucun collaborateur actif.</p>
                            <?php else: ?>
                                <canvas id="occupationChart" height="180"></canvas>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12 col-xl-6">
                    <?php renderDashboardList('Echeances urgentes', $dashboard['overdueDeadlines'] ?? [], ['title', 'company_name', 'deadline_date']); ?>
                    <?php renderDashboardList('Factures impayees', $dashboard['unpaidAlerts'] ?? [], ['invoice_number', 'company_name', 'balance_due']); ?>
                </div>
                <div class="col-12 col-xl-6">
                    <?php renderDashboardList('Top clients', $dashboard['topClients'] ?? [], ['company_name', 'total_paid']); ?>
                    <?php renderDashboardList('Documents nouveaux', $dashboard['newDocuments'] ?? [], ['title', 'company_name', 'uploaded_at']); ?>
                    <?php renderDashboardList('Timesheets saisis', $dashboard['pendingTimesheets'] ?? [], ['full_name', 'work_date', 'hours_worked']); ?>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
            const revenueData = <?php echo json_encode($monthlyRevenue, JSON_THROW_ON_ERROR); ?>;
            const missionData = <?php echo json_encode($missionStatuses, JSON_THROW_ON_ERROR); ?>;
            const occupationData = <?php echo json_encode($occupation, JSON_THROW_ON_ERROR); ?>;
            if (document.getElementById('revenueChart')) {
                new Chart(document.getElementById('revenueChart'), {
                    type: 'bar',
                    data: { labels: revenueData.map(row => row.label), datasets: [{ label: 'FCFA', data: revenueData.map(row => Number(row.amount)), backgroundColor: '#2563eb' }] },
                    options: { responsive: true, plugins: { legend: { display: false } } }
                });
            }
            if (document.getElementById('missionsChart')) {
                new Chart(document.getElementById('missionsChart'), {
                    type: 'doughnut',
                    data: { labels: Object.keys(missionData), datasets: [{ data: Object.values(missionData), backgroundColor: ['#64748b','#0d6efd','#198754','#dc3545','#6c757d'] }] },
                    options: { responsive: true }
                });
            }
            if (document.getElementById('occupationChart')) {
                new Chart(document.getElementById('occupationChart'), {
                    type: 'bar',
                    data: { labels: occupationData.map(row => row.full_name), datasets: [{ label: '% occupation', data: occupationData.map(row => Number(row.occupation_rate)), backgroundColor: '#198754' }] },
                    options: { responsive: true, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } }
                });
            }
            </script>
        <?php elseif (in_array($role, ['COLLABORATEUR', 'STAGIAIRE'], true)): ?>
            <div class="row g-4">
                <div class="col-12 col-xl-6"><?php renderDashboardList('Mes missions en cours', $dashboard['myMissions'] ?? [], ['title', 'status', 'planned_end_date']); ?></div>
                <div class="col-12 col-xl-6">
                    <div class="card shadow-sm mb-4"><div class="card-body">
                        <h2 class="h6">Mes timesheets</h2>
                        <p class="mb-0">Saisis: <?php echo e((string) (($dashboard['timesheets']['SAISI'] ?? 0))); ?> | Valides: <?php echo e((string) (($dashboard['timesheets']['VALIDE'] ?? 0))); ?> | Refuses: <?php echo e((string) (($dashboard['timesheets']['REFUSE'] ?? 0))); ?></p>
                    </div></div>
                    <div class="card shadow-sm mb-4"><div class="card-body">Notifications non lues: <?php echo e((string) ($dashboard['unreadNotifications'] ?? 0)); ?></div></div>
                    <?php renderDashboardList('Echeances de mes missions', $dashboard['deadlines'] ?? [], ['title', 'deadline_date', 'status']); ?>
                </div>
            </div>
        <?php elseif ($role === 'CLIENT'): ?>
            <div class="row g-4">
                <div class="col-12 col-xl-6">
                    <?php renderDashboardList('Mes missions', $dashboard['missions'] ?? [], ['title', 'status', 'planned_end_date']); ?>
                    <?php renderDashboardList('Mes echeances', $dashboard['deadlines'] ?? [], ['title', 'deadline_date', 'status']); ?>
                </div>
                <div class="col-12 col-xl-6">
                    <div class="card shadow-sm mb-4"><div class="card-body">Notifications non lues: <?php echo e((string) ($dashboard['unreadNotifications'] ?? 0)); ?></div></div>
                    <?php renderDashboardList('Mes documents', $dashboard['documents'] ?? [], ['title', 'status', 'uploaded_at']); ?>
                    <?php renderDashboardList('Mes factures impayees', $dashboard['unpaidInvoices'] ?? [], ['invoice_number', 'due_date', 'balance_due']); ?>
                    <?php renderDashboardList('Mes lettres de mission', $dashboard['letters'] ?? [], ['title', 'status', 'sent_at', 'signed_at']); ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
function renderDashboardList(string $title, array $rows, array $columns): void
{
    ?>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6"><?php echo e($title); ?></h2>
            <?php if ($rows === []): ?>
                <p class="text-muted mb-0">Aucune donnee a afficher.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <?php foreach ($columns as $column): ?>
                                    <td><?php echo e((string) ($row[$column] ?? '')); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
