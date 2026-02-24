<?php
/**
 * Script di verifica file temporaneo
 * Controlla l'esistenza di file critici e mostra il contenuto delle cartelle
 */

echo "========================================\n";
echo "VERIFICA FILE DEL SISTEMA\n";
echo "========================================\n\n";

// 1. Verifica config/database.php
$file1 = 'config/database.php';
echo "1. Verifica: {$file1}\n";
echo "   Esiste: " . (file_exists($file1) ? 'SI ✓' : 'NO ✗') . "\n\n";

// 2. Verifica includes/env_loader.php
$file2 = 'includes/env_loader.php';
echo "2. Verifica: {$file2}\n";
echo "   Esiste: " . (file_exists($file2) ? 'SI ✓' : 'NO ✗') . "\n\n";

// 3. Verifica includes/functions.php
$file3 = 'includes/functions.php';
echo "3. Verifica: {$file3}\n";
echo "   Esiste: " . (file_exists($file3) ? 'SI ✓' : 'NO ✗') . "\n\n";

// 4. Verifica .env
$file4 = '.env';
echo "4. Verifica: {$file4}\n";
echo "   Esiste: " . (file_exists($file4) ? 'SI ✓' : 'NO ✗') . "\n\n";

echo "========================================\n";
echo "CONTENUTO CARTELLA config/\n";
echo "========================================\n";
$files = scandir('config/');
if ($files !== false) {
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $path = 'config/' . $file;
            $type = is_dir($path) ? '[DIR] ' : '[FILE]';
            echo "   {$type} {$file}\n";
        }
    }
} else {
    echo "   ERRORE: Impossibile leggere la cartella\n";
}

echo "\n========================================\n";
echo "CONTENUTO CARTELLA includes/\n";
echo "========================================\n";
$files = scandir('includes/');
if ($files !== false) {
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $path = 'includes/' . $file;
            $type = is_dir($path) ? '[DIR] ' : '[FILE]';
            echo "   {$type} {$file}\n";
        }
    }
} else {
    echo "   ERRORE: Impossibile leggere la cartella\n";
}

echo "\n========================================\n";
echo "VERIFICA COMPLETATA\n";
echo "========================================\n";
