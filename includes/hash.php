<?php
function generateRoomHash(): string {
    $data  = microtime(true) . '|' . bin2hex(random_bytes(8));
    return hash('sha256', $data);
}
function validateRoomHash(string $hash): bool {
    return (bool)preg_match('/^[0-9a-f]{64}$/i', $hash);
}