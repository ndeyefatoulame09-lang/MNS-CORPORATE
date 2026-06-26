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
				<li class="nav-item mb-2"><a class="nav-link" href="/MNS_CORPORATE/clients.php">Clients</a></li>
			<?php endif; ?>
		</ul>
	<?php else: ?>
		<div class="text-muted">Connectez-vous pour voir le menu</div>
	<?php endif; ?>
</nav>