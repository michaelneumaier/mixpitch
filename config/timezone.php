<?php

return [
    'default' => 'America/New_York',
    
    'user_selectable' => [
        // North America
        'America/New_York' => 'Eastern Time (EST/EDT)',
        'America/Chicago' => 'Central Time (CST/CDT)', 
        'America/Denver' => 'Mountain Time (MST/MDT)',
        'America/Los_Angeles' => 'Pacific Time (PST/PDT)',
        'America/Phoenix' => 'Arizona Time (MST)',
        'America/Anchorage' => 'Alaska Time (AKST/AKDT)',
        'Pacific/Honolulu' => 'Hawaii Time (HST)',
        
        // International
        'UTC' => 'UTC (Coordinated Universal Time)',
        'Europe/London' => 'London (GMT/BST)',
        'Europe/Paris' => 'Paris (CET/CEST)',
        'Europe/Berlin' => 'Berlin (CET/CEST)',
        'Europe/Madrid' => 'Madrid (CET/CEST)',
        'Europe/Rome' => 'Rome (CET/CEST)',
        'Asia/Tokyo' => 'Tokyo (JST)',
        'Asia/Shanghai' => 'Shanghai (CST)',
        'Asia/Kolkata' => 'Mumbai (IST)',
        'Australia/Sydney' => 'Sydney (AEDT/AEST)',
        'Australia/Melbourne' => 'Melbourne (AEDT/AEST)',
    ],
    
    'display_formats' => [
        'datetime' => 'M j, Y g:i A T',
        'date' => 'M j, Y',
        'time' => 'g:i A T',
        'short_date' => 'M j',
        'iso' => 'c', // ISO 8601 format
    ],
]; 