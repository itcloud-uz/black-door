<?php
$hash = '$2y$12$PMvUPLkbc/Zkq8DJvv16ce2lKP4DjRWmJ2tkPh7WK4w0frB.r6gQO';
for ($i = 0; $i <= 9999; $i++) {
    $pin = str_pad($i, 4, '0', STR_PAD_LEFT);
    if (password_verify($pin, $hash)) {
        echo "FOUND PIN: " . $pin . "\n";
        exit;
    }
}
echo "PIN NOT FOUND in 4 digits\n";
