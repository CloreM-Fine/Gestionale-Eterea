<?php
/**
 * Eterea Gestionale
 * Redirect Listini -> Preventivi
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

// Redirect alla pagina preventivi
header('Location: preventivi.php');
exit;
