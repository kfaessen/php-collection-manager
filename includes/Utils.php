<?php
namespace CollectionManager;

class Utils 
{
    /**
     * Sanitize user input
     */
    public static function sanitize($input) 
    {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate barcode format
     */
    public static function validateBarcode($barcode) 
    {
        return preg_match('/^[0-9]{8,13}$/', $barcode);
    }
    
    /**
     * Format date for display
     */
    public static function formatDate($date) 
    {
        if (!$date) return '';
        
        $dt = new \DateTime($date);
        return $dt->format('d-m-Y H:i');
    }
    
    /**
     * Send JSON success response
     */
    public static function successResponse($data = null, $message = 'Success') 
    {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
    
    /**
     * Send JSON error response
     */
    public static function errorResponse($message = 'Error') 
    {
        header('Content-Type: application/json');
        header('HTTP/1.1 400 Bad Request');
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit;
    }
    
    /**
     * Generate pagination HTML
     */
    public static function generatePagination($currentPage, $totalItems, $itemsPerPage, $baseUrl) 
    {
        $totalPages = ceil($totalItems / $itemsPerPage);
        
        if ($totalPages <= 1) {
            return '';
        }
        
        $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
        
        // Previous button
        if ($currentPage > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . ($currentPage - 1) . '">Vorige</a></li>';
        }
        
        // Page numbers
        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);
        
        for ($i = $start; $i <= $end; $i++) {
            $active = ($i == $currentPage) ? ' active' : '';
            $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a></li>';
        }
        
        // Next button
        if ($currentPage < $totalPages) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . ($currentPage + 1) . '">Volgende</a></li>';
        }
        
        $html .= '</ul></nav>';
        
        return $html;
    }
} 