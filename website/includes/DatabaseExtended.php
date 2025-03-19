<?php

/**
 * Function to fetch website content from the database
 * @param string $section The section to fetch (e.g., 'logo', 'color')
 * @return array The fetched data
 */
function getWebsiteContent($section) {
    // Create a new database instance
    $db = new Database();
    
    try {
        // Get the connection
        $connection = $db->connect();
        
        // Prepare and execute the query
        $stmt = $connection->prepare("SELECT * FROM website_content WHERE section = :section");
        $stmt->bindParam(':section', $section, PDO::PARAM_STR);
        $stmt->execute();
        
        // Fetch the results
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Return the first result or an empty array
        return $result ? $result[0] : [];
    } catch (Exception $e) {
        // Log the error (you might want to use a proper logging system)
        error_log("Database error: " . $e->getMessage());
        
        // Return an empty array in case of error
        return [];
    }
}

/**
 * Convert decimal to hex color
 * @param float $decimal The decimal value
 * @return string The hex color code
 */
function decimalToHex($decimal) {
    $hex = dechex(abs(floor($decimal * 16777215)));
    // Ensure hex values are properly formatted with leading zeros
    return '#' . str_pad($hex, 6, '0', STR_PAD_LEFT);
}