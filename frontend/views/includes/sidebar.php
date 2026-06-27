<?php
require_once __DIR__ . '/../../../backend/includes/session.php';
require_once __DIR__ . '/../../../backend/includes/helpers.php';

// Afficher le menu seulement pour les utilisateurs authentifiés
$user = currentUser();
?>
<nav class="bg-white border-end" style="min-height:100vh; padding:1rem;">
	<div class="mb-4">
		<a href="/MNS_CORPORATE/index.php" class="text-decoration-none"><strong>MNS CORPORATE</strong></a>
	</div>

	<?php if ($user !== null): ?>
		<ul class="nav flex-column">
			<li class="nav-item mb-2"><a class="nav-link" href="/MNS_CORPORATE/index.php">Dashboard</a></li>
			<?php if (isset($user['role']) && $user['role'] === 'EXPERT'): ?>
				<li class="nav-item mb-2"><a class="nav-link" href="/MNS_CORPORATE/frontend/views/clients/list.php">Clients</a></li>
				<li class="nav-item mb-2"><a class="nav-link" href="/MNS_CORPORATE/frontend/views/missions/list.php">Missions</a></li>
				<li class="nav-item mb-2"><a class="nav-link" href="/MNS_CORPORATE/frontend/views/missions/catalog_list.php">Catalogue missions</a></li>
				<li class="nav-item mb-2"><a class="nav-link" href="/MNS_CORPORATE/frontend/views/notifications/list.php">Notifications</a></li>
				<li class="nav-item mb-2"><a class="nav-link" href="/MNS_CORPORATE/frontend/views/timesheets/list.php">Gestion des timesheets</a></li>
				<li class="nav-item mb-2"><a class="nav-link" href="/MNS_CORPORATE/frontend/views/timesheets/summary.php">Synthese temps</a></li>
			<?php elseif (isset($user['role']) && in_array($user['role'], ['COLLABORATEUR', 'STAGIAIRE'], true)): ?>
				<li class="nav-item mb-2"><a class="nav-link" href="/MNS_CORPORATE/frontend/views/notifications/list.php">Mes notifications</a></li>
				<li class="nav-item mb-2"><a class="nav-link" href="/MNS_CORPORATE/frontend/views/timesheets/list.php">Mes timesheets</a></li>
				<li class="nav-item mb-2"><a class="nav-link" href="/MNS_CORPORATE/frontend/views/timesheets/create.php">Ajouter un temps passe</a></li>
			<?php elseif (isset($user['role']) && $user['role'] === 'CLIENT'): ?>
				<li class="nav-item mb-2"><a class="nav-link" href="/MNS_CORPORATE/frontend/views/notifications/list.php">Mes notifications</a></li>
			<?php endif; ?>
			<li class="nav-item mb-2"><a class="nav-link" href="/MNS_CORPORATE/frontend/views/deadlines/list.php">Echeances fiscales</a></li>
			<li class="nav-item mb-2"><a class="nav-link" href="/MNS_CORPORATE/frontend/views/documents/list.php">Documents</a></li>
		</ul>
	<?php else: ?>
		<div class="text-muted">Connectez-vous pour voir le menu</div>
	<?php endif; ?>
</nav>
