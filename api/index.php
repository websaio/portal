<?php
/**
 * API router for the Tuition Management System
 * Handles all API requests
 */

// Start session
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is authenticated for protected routes
require_once __DIR__ . '/../auth/auth.php';

// Get request method and endpoint
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// Extract the endpoint from the request URI
$base_path = '/api/';
$endpoint = substr($request_uri, strpos($request_uri, $base_path) + strlen($base_path));

// Remove query string if present
if (strpos($endpoint, '?') !== false) {
    $endpoint = substr($endpoint, 0, strpos($endpoint, '?'));
}

// Route the request
try {
    // Authentication check for protected routes
    if ($endpoint !== 'auth/login' && !is_authenticated()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized. Please log in.'
        ]);
        exit;
    }
    
    // Admin-only routes
    $admin_routes = [
        'users', 
        'users/',
        'settings'
    ];
    
    foreach ($admin_routes as $route) {
        if (strpos($endpoint, $route) === 0 && !is_admin()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Forbidden. Admin access required.'
            ]);
            exit;
        }
    }
    
    // Route the request based on endpoint
    $parts = explode('/', $endpoint);
    $route_file = __DIR__ . '/routes/' . $parts[0] . '_routes.php';
    
    if (file_exists($route_file)) {
        require_once $route_file;
        
        $handler_function = $parts[0] . '_' . strtolower($method) . '_handler';
        
        if (function_exists($handler_function)) {
            // Get request data
            $data = [];
            if ($method === 'GET') {
                $data = $_GET;
            } else {
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                if ($data === null && !empty($input)) {
                    $data = $_POST;
                }
            }
            
            // Call the handler function
            $response = $handler_function($parts, $data);
            echo json_encode($response);
        } else {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
        }
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Endpoint not found'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
