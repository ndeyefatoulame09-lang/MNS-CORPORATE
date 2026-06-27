<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../backend/includes/auth_check.php';
require_once __DIR__ . '/../../../backend/includes/helpers.php';

$user = currentUser();
$role = is_array($user) ? (string) ($user['role'] ?? '') : '';
$fullName = is_array($user) ? (string) ($user['full_name'] ?? '') : '';
$currentPath = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
$sidebarBlue = '#003399';

$links = [];
if ($role === 'EXPERT') {
    $links = [
        ['Tableau de bord', '/MNS_CORPORATE/index.php'],
        ['Clients', '/MNS_CORPORATE/frontend/views/clients/list.php'],
        ['Missions', '/MNS_CORPORATE/frontend/views/missions/list.php'],
        ['Catalogue des missions', '/MNS_CORPORATE/frontend/views/missions/catalog_list.php'],
        ['Echeances fiscales', '/MNS_CORPORATE/frontend/views/deadlines/list.php'],
        ['Documents', '/MNS_CORPORATE/frontend/views/documents/list.php'],
        ['Notifications', '/MNS_CORPORATE/frontend/views/notifications/list.php'],
        ['Gestion des timesheets', '/MNS_CORPORATE/frontend/views/timesheets/list.php'],
        ['Synthese temps par mission', '/MNS_CORPORATE/frontend/views/timesheets/summary.php'],
        ['Factures', '/MNS_CORPORATE/frontend/views/invoices/list.php'],
        ['Creer une facture', '/MNS_CORPORATE/frontend/views/invoices/create.php'],
        ['Paiements', '/MNS_CORPORATE/frontend/views/payments/list.php'],
        ['Balance agee', '/MNS_CORPORATE/frontend/views/payments/balance_aged.php'],
        ['Lettres de mission', '/MNS_CORPORATE/frontend/views/engagement_letters/list.php'],
        ['Exports', '/MNS_CORPORATE/frontend/views/exports/index.php'],
        ['Sauvegarde base de donnees', '/MNS_CORPORATE/frontend/views/exports/backup.php'],
        ['Journal d audit', '/MNS_CORPORATE/frontend/views/audit_logs/list.php'],
    ];
} elseif (in_array($role, ['COLLABORATEUR', 'STAGIAIRE'], true)) {
    $links = [
        ['Tableau de bord', '/MNS_CORPORATE/index.php'],
        ['Mes missions', '/MNS_CORPORATE/frontend/views/missions/list.php'],
        ['Mes echeances', '/MNS_CORPORATE/frontend/views/deadlines/list.php'],
        ['Documents lies a mes missions', '/MNS_CORPORATE/frontend/views/documents/list.php'],
        ['Mes notifications', '/MNS_CORPORATE/frontend/views/notifications/list.php'],
        ['Mes timesheets', '/MNS_CORPORATE/frontend/views/timesheets/list.php'],
        ['Ajouter un temps passe', '/MNS_CORPORATE/frontend/views/timesheets/create.php'],
    ];
} elseif ($role === 'CLIENT') {
    $links = [
        ['Mon tableau de bord', '/MNS_CORPORATE/index.php'],
        ['Mes missions', '/MNS_CORPORATE/frontend/views/missions/list.php'],
        ['Mes echeances fiscales', '/MNS_CORPORATE/frontend/views/deadlines/list.php'],
        ['Mes documents', '/MNS_CORPORATE/frontend/views/documents/list.php'],
        ['Deposer un document', '/MNS_CORPORATE/frontend/views/documents/upload.php'],
        ['Mes factures', '/MNS_CORPORATE/frontend/views/invoices/list.php'],
        ['Mes paiements', '/MNS_CORPORATE/frontend/views/payments/list.php'],
        ['Mes lettres de mission', '/MNS_CORPORATE/frontend/views/engagement_letters/list.php'],
        ['Mes notifications', '/MNS_CORPORATE/frontend/views/notifications/list.php'],
    ];
}
?>
<div class="d-lg-none position-fixed top-0 start-0 m-2" style="z-index: 1040;">
    <button class="btn text-white border-white" style="background-color: <?php echo e($sidebarBlue); ?>;" type="button" data-bs-toggle="offcanvas" data-bs-target="#mnsSidebar" aria-controls="mnsSidebar">
        Menu
    </button>
</div>

<nav class="border-end d-none d-lg-flex flex-column flex-shrink-0" style="min-height:100vh; width:260px; padding:1rem; background-color: <?php echo e($sidebarBlue); ?>;">
    <?php renderSidebarContent($user, $fullName, $role, $links, $currentPath, $sidebarBlue); ?>
</nav>

<div class="offcanvas offcanvas-start d-lg-none text-white" style="background-color: <?php echo e($sidebarBlue); ?>;" tabindex="-1" id="mnsSidebar" aria-labelledby="mnsSidebarLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="mnsSidebarLabel">MNS CORPORATE</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <?php renderSidebarContent($user, $fullName, $role, $links, $currentPath, $sidebarBlue); ?>
    </div>
</div>

<?php
function renderSidebarContent(?array $user, string $fullName, string $role, array $links, string $currentPath, string $sidebarBlue): void
{
    ?>
    <div class="mb-4">
        <a href="/MNS_CORPORATE/index.php" class="text-decoration-none text-white"><strong>MNS CORPORATE</strong></a>
        <?php if ($user !== null): ?>
            <div class="small text-white-50 mt-2">
                <div><?php echo e($fullName); ?></div>
                <div><?php echo e($role); ?></div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($user !== null): ?>
        <ul class="nav flex-column flex-grow-1">
            <?php foreach ($links as [$label, $url]): ?>
                <?php $isActive = sidebarLinkIsActive((string) $url, $currentPath); ?>
                <li class="nav-item mb-2">
                    <a
                        class="nav-link rounded <?php echo $isActive ? 'fw-semibold' : ''; ?>"
                        style="<?php echo $isActive ? 'background-color:#fff;color:' . e($sidebarBlue) . ';' : 'color:#fff;'; ?>"
                        href="<?php echo e($url); ?>"
                    ><?php echo e($label); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="pt-3 border-top border-light">
            <a class="btn btn-outline-light w-100" href="/MNS_CORPORATE/logout.php">Deconnexion</a>
        </div>
    <?php else: ?>
        <div class="text-white-50">Connectez-vous pour voir le menu.</div>
    <?php endif; ?>
    <?php
}

function sidebarLinkIsActive(string $url, string $currentPath): bool
{
    $path = parse_url($url, PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        return false;
    }

    return $currentPath === $path;
}
