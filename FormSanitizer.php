<?php
/**
 * Form Sanitizer Class
 * A utility class to sanitize and validate form inputs
 */
class FormSanitizer {
    /**
     * Sanitize a string input
     * 
     * @param string $input The input to sanitize
     * @return string The sanitized input
     */
    public static function sanitizeString($input) {
        // Trim whitespace, convert HTML special chars, and strip tags
        $sanitized = trim($input);
        $sanitized = strip_tags($sanitized);
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        return $sanitized;
    }
    
    /**
     * Sanitize an email address
     * 
     * @param string $email The email to sanitize
     * @return string|false The sanitized email or false if invalid
     */
    public static function sanitizeEmail($email) {
        $email = trim($email);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        // Validate the email format
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }
        return false;
    }
    
    /**
     * Sanitize an integer input
     * 
     * @param mixed $input The input to sanitize
     * @return int The sanitized integer
     */
    public static function sanitizeInt($input) {
        return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Sanitize a float input
     * 
     * @param mixed $input The input to sanitize
     * @return float The sanitized float
     */
    public static function sanitizeFloat($input) {
        // Convert comma to dot if needed (for European formats)
        $input = str_replace(',', '.', $input);
        return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    /**
     * Sanitize a URL
     * 
     * @param string $url The URL to sanitize
     * @return string|false The sanitized URL or false if invalid
     */
    public static function sanitizeUrl($url) {
        $url = trim($url);
        $url = filter_var($url, FILTER_SANITIZE_URL);
        
        // Validate the URL format
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        return false;
    }
    
    /**
     * Sanitize input for database queries (prevent SQL injection)
     * Note: This should be used with prepared statements
     * 
     * @param string $input The input to sanitize
     * @param mysqli $conn MySQLi connection object
     * @return string The sanitized input
     */
    public static function sanitizeForDatabase($input, $conn) {
        if ($input === null) {
            return null;
        }
        
        if (is_array($input)) {
            $sanitized = [];
            foreach ($input as $key => $value) {
                $sanitized[$key] = self::sanitizeForDatabase($value, $conn);
            }
            return $sanitized;
        }
        
        return $conn->real_escape_string($input);
    }
    
    /**
     * Sanitize an array of form inputs
     * 
     * @param array $formData The form data array
     * @param mysqli $conn Optional MySQLi connection for database sanitization
     * @return array The sanitized form data
     */
    public static function sanitizeFormData($formData, $conn = null) {
        $sanitized = [];
        
        foreach ($formData as $key => $value) {
            // Skip sanitization for certain special fields (like file uploads)
            if (is_array($value) && isset($value['tmp_name'])) {
                $sanitized[$key] = $value;
                continue;
            }
            
            // Handle array inputs
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeFormData($value, $conn);
                continue;
            }
            
            // Handle different input types based on field name
            if (strpos($key, 'email') !== false) {
                $sanitized[$key] = self::sanitizeEmail($value);
            } else if (strpos($key, 'url') !== false || strpos($key, 'website') !== false) {
                $sanitized[$key] = self::sanitizeUrl($value);
            } else if (preg_match('/(price|amount|total|cost|fee|number|num|qty|quantity)$/i', $key)) {
                if (strpos($value, '.') !== false) {
                    $sanitized[$key] = self::sanitizeFloat($value);
                } else {
                    $sanitized[$key] = self::sanitizeInt($value);
                }
            } else {
                $sanitized[$key] = self::sanitizeString($value);
            }
            
            // Additional database sanitization if connection provided
            if ($conn !== null) {
                $sanitized[$key] = self::sanitizeForDatabase($sanitized[$key], $conn);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Validate a form field
     * 
     * @param mixed $value The value to validate
     * @param string $type The type of validation
     * @param array $options Additional validation options
     * @return boolean Whether the value is valid
     */
    public static function validateField($value, $type, $options = []) {
        switch ($type) {
            case 'required':
                return !empty($value);
                
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
                
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) !== false;
                
            case 'numeric':
                return is_numeric($value);
                
            case 'integer':
                return filter_var($value, FILTER_VALIDATE_INT) !== false;
                
            case 'alpha':
                return preg_match('/^[a-zA-Z]+$/', $value);
                
            case 'alphanumeric':
                return preg_match('/^[a-zA-Z0-9]+$/', $value);
                
            case 'min_length':
                return strlen($value) >= $options['min'];
                
            case 'max_length':
                return strlen($value) <= $options['max'];
                
            case 'length_between':
                $length = strlen($value);
                return $length >= $options['min'] && $length <= $options['max'];
                
            case 'date':
                $format = $options['format'] ?? 'Y-m-d';
                $date = DateTime::createFromFormat($format, $value);
                return $date && $date->format($format) === $value;
                
            case 'phone':
                // Basic phone validation - can be customized for specific formats
                return preg_match('/^[0-9+\-\(\) ]+$/', $value);
                
            case 'matches':
                return $value === $options['match_against'];
                
            case 'in_array':
                return in_array($value, $options['values']);
                
            default:
                return true;
        }
    }
    
    /**
     * Validate a complete form
     * 
     * @param array $formData The form data to validate
     * @param array $rules The validation rules
     * @return array An array of validation errors, empty if no errors
     */
    public static function validateForm($formData, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $formData[$field] ?? null;
            
            foreach ($fieldRules as $rule) {
                $ruleType = is_array($rule) ? $rule['type'] : $rule;
                $ruleOptions = is_array($rule) && isset($rule['options']) ? $rule['options'] : [];
                
                if (!self::validateField($value, $ruleType, $ruleOptions)) {
                    $errorMessage = is_array($rule) && isset($rule['message']) 
                        ? $rule['message'] 
                        : "The {$field} field failed {$ruleType} validation.";
                    
                    $errors[$field][] = $errorMessage;
                    break; // Stop validating this field after first error
                }
            }
        }
        
        return $errors;
    }
}