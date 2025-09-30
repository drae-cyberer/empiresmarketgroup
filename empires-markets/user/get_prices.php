<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Ensure the user is logged in to access price data
require_login();

// Set the content type to JSON
header('Content-Type: application/json');

try {
    // Fetch live market data from the database
    $market_data = get_market_data();

    if ($market_data === false) {
        throw new Exception("Failed to retrieve market data.");
    }

    // Prepare the data for JSON output
    $prices = [];
    foreach ($market_data as $data) {
        $prices[] = [
            'symbol' => $data['symbol'],
            'price' => number_format($data['price'], 5), // Format for display
            'change' => number_format($data['change_percent'], 2),
            'name' => $data['name']
        ];
    }

    // Return the data as a JSON object
    echo json_encode(['success' => true, 'prices' => $prices]);

} catch (Exception $e) {
    // Return an error message in JSON format
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching market data: ' . $e->getMessage()
    ]);
}
?>