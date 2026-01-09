<?php

namespace App\Helpers;

class PhoneNumberHelper
{
    /**
     * Normalize phone number to international format (+62xxxxxxxxxx)
     * Accepts various formats:
     * - +62 813-9577-7706
     * - 62 813-9577-7706
     * - 081395777706
     * - 813-9577-7706
     * - 6281395777706
     * - +6281395777706
     * 
     * @param string $phoneNumber
     * @return string|null Normalized phone number in format +62xxxxxxxxxx or null if invalid
     */
    public static function normalize(string $phoneNumber): ?string
    {
        // Remove all whitespace, dashes, and other non-digit characters except +
        $cleaned = preg_replace('/[\s\-\(\)\.]/', '', trim($phoneNumber));
        
        // Remove + if present (we'll add it back at the end)
        $hasPlus = strpos($cleaned, '+') !== false;
        $cleaned = str_replace('+', '', $cleaned);
        
        // Remove all non-digit characters
        $cleaned = preg_replace('/[^0-9]/', '', $cleaned);
        
        // If empty after cleaning, return null
        if (empty($cleaned)) {
            return null;
        }
        
        // If starts with 0, replace with 62 (Indonesia)
        if (substr($cleaned, 0, 1) === '0') {
            $cleaned = '62' . substr($cleaned, 1);
        }
        // If starts with 8 (without country code), add 62
        elseif (substr($cleaned, 0, 1) === '8' && substr($cleaned, 0, 2) !== '62') {
            $cleaned = '62' . $cleaned;
        }
        // If already starts with 62, keep it
        elseif (substr($cleaned, 0, 2) === '62') {
            // Already in correct format
        }
        // If starts with other numbers, assume it's already international format
        // But for safety, we'll validate it's a reasonable length
        
        // Validate length (should be 10-15 digits total)
        // Indonesia: 62 + 8-12 digits = 10-14 total digits
        $length = strlen($cleaned);
        if ($length < 10 || $length > 15) {
            return null;
        }
        
        // Ensure it starts with 62 (Indonesia country code)
        if (substr($cleaned, 0, 2) !== '62') {
            return null;
        }
        
        // Return with + prefix
        return '+' . $cleaned;
    }
    
    /**
     * Validate phone number format
     * 
     * @param string $phoneNumber
     * @return bool
     */
    public static function validate(string $phoneNumber): bool
    {
        $normalized = self::normalize($phoneNumber);
        return $normalized !== null;
    }
    
    /**
     * Normalize phone number for chatId (without + prefix)
     * Returns format: 62xxxxxxxxxx (for use in chatId like 62xxxxxxxxxx@c.us)
     * 
     * @param string $phoneNumber
     * @return string|null Normalized phone number without + prefix or null if invalid
     */
    public static function normalizeForChatId(string $phoneNumber): ?string
    {
        $normalized = self::normalize($phoneNumber);
        if (!$normalized) {
            return null;
        }
        
        // Remove + prefix for chatId usage
        return ltrim($normalized, '+');
    }
    
    /**
     * Format phone number for display
     * 
     * @param string $phoneNumber
     * @return string
     */
    public static function format(string $phoneNumber): string
    {
        $normalized = self::normalize($phoneNumber);
        if (!$normalized) {
            return $phoneNumber;
        }
        
        // Format: 62-812-3456-7890
        if (strlen($normalized) >= 12) {
            return substr($normalized, 0, 2) . '-' . 
                   substr($normalized, 2, 3) . '-' . 
                   substr($normalized, 5, 4) . '-' . 
                   substr($normalized, 9);
        }
        
        return $normalized;
    }
}


