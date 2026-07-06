<?php
/**
 * Response Handler - Standardized API responses
 * GlobalWays Backend
 */

class Response {
    /**
     * Send success response
     */
    public static function success($data = null, $message = 'Success', $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    /**
     * Send error response
     */
    public static function error($message = 'Error', $statusCode = 400, $errors = null) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    /**
     * Send validation error
     */
    public static function validationError($errors) {
        self::error('Validation failed', 422, $errors);
    }

    /**
     * Send not found response
     */
    public static function notFound($message = 'Resource not found') {
        self::error($message, 404);
    }

    /**
     * Send unauthorized response
     */
    public static function unauthorized($message = 'Unauthorized') {
        self::error($message, 401);
    }

    /**
     * Send server error response
     */
    public static function serverError($message = 'Internal server error') {
        self::error($message, 500);
    }
}
?>
