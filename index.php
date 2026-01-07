<?php declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!is_array($data) || empty($data['pdf_b64']) || !array_key_exists('password', $data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing pdf_b64/password']);
    exit;
}

$pdfBytes = base64_decode((string)$data['pdf_b64'], true);
if ($pdfBytes === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid base64']);
    exit;
}

$password = (string)$data['password'];

$workDir = sys_get_temp_dir() . '/pdfdec_' . bin2hex(random_bytes(8));
@mkdir($workDir, 0700, true);

$inPath  = $workDir . '/in.pdf';
$outPath = $workDir . '/out.pdf';

file_put_contents($inPath, $pdfBytes);

$cmd = sprintf(
    'qpdf --password=%s --decrypt %s %s 2>&1',
    escapeshellarg($password),
    escapeshellarg($inPath),
    escapeshellarg($outPath)
);

exec($cmd, $outputLines, $exitCode);

if ($exitCode !== 0 || !file_exists($outPath)) {

    @unlink($inPath);
    @rmdir($workDir);

    http_response_code(400);
    echo json_encode([
        'error' => 'Failed to decrypt (wrong password or unsupported encryption)',
        'details' => implode("\n", $outputLines),
    ]);
    exit;
}

$outBytes = file_get_contents($outPath);

@unlink($inPath);
@unlink($outPath);
@rmdir($workDir);

echo json_encode([
    'pdf_b64' => base64_encode($outBytes),
]);
