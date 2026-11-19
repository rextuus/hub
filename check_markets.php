<?php

require 'vendor/autoload.php';

use App\Tool\BetAI\Entity\BetSuggestion;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

$dsn = getenv('DATABASE_URL');
$dsn = str_replace('mysql://app:!ChangeMe!@database:3306/app', 'mysql:host=127.0.0.1;dbname=hub_db', $dsn); // Adjust based on local environment if needed, or assume default connection
// Actually, I can just use Doctrine if I load the kernel.

// Simple approach: try to connect via PDO directly
$pdo = new PDO('mysql:host=127.0.0.1;dbname=hub_db', 'app', '!ChangeMe!');
$stmt = $pdo->query("SELECT DISTINCT market FROM bet_ai_betsuggestion WHERE market_type = 'UNKNOWN'");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

print_r($results);
