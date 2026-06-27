<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../backend/controllers/dashboard_controller.php';
requireRole(['EXPERT']);
handleDashboardRequest();
