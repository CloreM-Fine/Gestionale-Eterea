<?php
/**
 * Eterea Gestionale - Pagina di errore
 * 
 * Gestisce errori HTTP 403, 404, 500, 503
 */

$errorCode = $_GET['code'] ?? '404';
$errorTitle = 'Errore';
$errorMessage = 'Si è verificato un errore imprevisto.';

switch ($errorCode) {
    case '403':
        $errorTitle = 'Accesso Negato';
        $errorMessage = 'Non hai i permessi necessari per accedere a questa risorsa.';
        http_response_code(403);
        break;
    case '404':
        $errorTitle = 'Pagina Non Trovata';
        $errorMessage = 'La pagina che stai cercando non esiste o è stata spostata.';
        http_response_code(404);
        break;
    case '500':
        $errorTitle = 'Errore del Server';
        $errorMessage = 'Si è verificato un errore interno. Riprova più tardi.';
        http_response_code(500);
        break;
    case '503':
        $errorTitle = 'Servizio Non Disponibile';
        $errorMessage = 'Il servizio è temporaneamente non disponibile. Riprova più tardi.';
        http_response_code(503);
        break;
    default:
        $errorCode = '404';
        $errorTitle = 'Pagina Non Trovata';
        $errorMessage = 'La pagina che stai cercando non esiste o è stata spostata.';
        http_response_code(404);
}

$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$homeUrl = $isLoggedIn ? 'dashboard' : '';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($errorTitle); ?> - Eterea Gestionale</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .eterea-bg { background-color: #f5f3ef; }
        .eterea-primary { color: #2d2d2d; }
        .eterea-azzurro { background-color: #9bc4d0; }
    </style>
</head>
<body class="eterea-bg min-h-screen flex items-center justify-center">
    <div class="text-center px-4">
        <div class="mb-8">
            <span class="text-9xl font-bold eterea-primary opacity-20"><?php echo htmlspecialchars($errorCode); ?></span>
        </div>
        <h1 class="text-3xl font-semibold eterea-primary mb-4"><?php echo htmlspecialchars($errorTitle); ?></h1>
        <p class="text-gray-600 mb-8 max-w-md mx-auto"><?php echo htmlspecialchars($errorMessage); ?></p>
        <a href="/<?php echo $homeUrl; ?>" class="inline-block eterea-azzurro text-white font-medium px-8 py-3 rounded-full hover:opacity-90 transition-opacity">
            <?php echo $isLoggedIn ? 'Torna alla Dashboard' : 'Torna alla Home'; ?>
        </a>
    </div>
</body>
</html>
