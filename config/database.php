<?php
/**
 * Database configuration and utilities
 */

// Database connection parameters
$db_host = 'localhost';
$db_user = 'agreengr_portalu';           // Change for production
$db_password = 'gVZ}0;^0-yma';           // Change for production
$db_name = 'agreengr_portal'; // Change for production

/**
 * Create a new database connection
 * 
 * @return mysqli Database connection
 */
function db_connect() {
    global $db_host, $db_user, $db_password, $db_name;
    
    $conn = new mysqli($db_host, $db_user, $db_password, $db_name);
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Database connection failed. Please check configuration.");
    }
    
    return $conn;
}

/**
 * Execute a SELECT query
 * 
 * @param string $query SQL query with placeholders
 * @param array $params Parameters for the query
 * @param string $types Types of parameters (i: integer, d: double, s: string, b: blob)
 * @return array|null Result set or null
 */
function db_select($query, $params = [], $types = '') {
    $conn = db_connect();
    $result = [];
    
    try {
        if (empty($params)) {
            // Simple query without parameters
            $query_result = $conn->query($query);
            
            if (!$query_result) {
                throw new Exception("Query failed: " . $conn->error);
            }
            
            if ($query_result->num_rows > 0) {
                while ($row = $query_result->fetch_assoc()) {
                    $result[] = $row;
                }
            }
            
            $query_result->close();
        } else {
            // Prepared statement with parameters
            $stmt = $conn->prepare($query);
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $query_result = $stmt->get_result();
            
            if ($query_result) {
                while ($row = $query_result->fetch_assoc()) {
                    $result[] = $row;
                }
            }
            
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return null;
    } finally {
        $conn->close();
    }
    
    return $result;
}

/**
 * Execute an INSERT, UPDATE, or DELETE query
 * 
 * @param string $query SQL query with placeholders
 * @param array $params Parameters for the query
 * @param string $types Types of parameters (i: integer, d: double, s: string, b: blob)
 * @return int|false Number of affected rows or false on failure
 */
function db_execute($query, $params = [], $types = '') {
    $conn = db_connect();
    $affected_rows = false;
    
    try {
        if (empty($params)) {
            // Simple query without parameters
            $success = $conn->query($query);
            
            if (!$success) {
                throw new Exception("Query failed: " . $conn->error);
            }
            
            $affected_rows = $conn->affected_rows;
        } else {
            // Prepared statement with parameters
            $stmt = $conn->prepare($query);
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    } finally {
        $conn->close();
    }
    
    return $affected_rows;
}

/**
 * Get the last inserted ID
 * 
 * @return int|null The last inserted ID or null on failure
 */
function db_last_insert_id() {
    $conn = db_connect();
    $insert_id = $conn->insert_id;
    $conn->close();
    
    return $insert_id;
}

/**
 * Begin a transaction
 * 
 * @return bool True on success, false on failure
 */
function db_begin_transaction() {
    $conn = db_connect();
    $result = $conn->begin_transaction();
    $conn->close();
    
    return $result;
}

/**
 * Commit a transaction
 * 
 * @return bool True on success, false on failure
 */
function db_commit() {
    $conn = db_connect();
    $result = $conn->commit();
    $conn->close();
    
    return $result;
}

/**
 * Rollback a transaction
 * 
 * @return bool True on success, false on failure
 */
function db_rollback() {
    $conn = db_connect();
    $result = $conn->rollback();
    $conn->close();
    
    return $result;
}
