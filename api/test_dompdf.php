<?php
/**
 * Test DOMPDF - Debug
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "=== TEST DOMPDF ===\n\n";

// Percorso corretto
$vendorPath = $_SERVER['DOCUMENT_ROOT'] . '/vendor/';
echo "Vendor path: $vendorPath\n";
echo "Vendor exists: " . (is_dir($vendorPath) ? 'YES' : 'NO') . "\n\n";

// Lista contenuto
if (is_dir($vendorPath)) {
    echo "Contenuto vendor/:\n";
    $items = scandir($vendorPath);
    foreach ($items as $item) {
        if ($item !== '.' && $item !== '..') {
            $fullPath = $vendorPath . $item;
            echo "  - $item (" . (is_dir($fullPath) ? 'dir' : 'file') . ")\n";
        }
    }
    echo "\n";
}

// Check specifico dompdf
$dompdfPath = $vendorPath . 'dompdf/';
echo "Dompdf path: $dompdfPath\n";
echo "Dompdf exists: " . (is_dir($dompdfPath) ? 'YES' : 'NO') . "\n";
echo "lib/Cpdf.php exists: " . (file_exists($dompdfPath . 'lib/Cpdf.php') ? 'YES' : 'NO') . "\n";
echo "src/Dompdf.php exists: " . (file_exists($dompdfPath . 'src/Dompdf.php') ? 'YES' : 'NO') . "\n\n";

// Prova a caricare
if (file_exists($dompdfPath . 'lib/Cpdf.php')) {
    echo "Loading Cpdf...\n";
    require_once $dompdfPath . 'lib/Cpdf.php';
    echo "Cpdf loaded OK\n";
    
    // Autoloader
    spl_autoload_register(function ($class) use ($dompdfPath) {
        $prefix = 'Dompdf\\';
        $baseDir = $dompdfPath . 'src/';
        
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        
        if (file_exists($file)) {
            require $file;
        }
    });
    
    if (class_exists('Dompdf\Dompdf')) {
        echo "Dompdf class FOUND\n";
        try {
            $dompdf = new \Dompdf\Dompdf();
            echo "Dompdf instantiated OK\n";
            $dompdf->loadHtml('<h1>Test PDF</h1>');
            $dompdf->render();
            echo "PDF generated SUCCESSFULLY!\n";
        } catch (Throwable $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Dompdf class NOT FOUND\n";
    }
} else {
    echo "Cpdf.php NOT FOUND\n";
}
