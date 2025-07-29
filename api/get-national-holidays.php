<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// No database connection needed for national holidays

try {
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    
    // Philippine National Holidays (Fixed dates)
    $nationalHolidays = [
        // Fixed Date Holidays
        $year . '-01-01' => 'New Year\'s Day',
        $year . '-04-09' => 'Day of Valor (Araw ng Kagitingan)',
        $year . '-05-01' => 'Labor Day',
        $year . '-06-12' => 'Independence Day',
        $year . '-08-21' => 'Ninoy Aquino Day',
        $year . '-08-30' => 'National Heroes Day',
        $year . '-11-30' => 'Bonifacio Day',
        $year . '-12-25' => 'Christmas Day',
        $year . '-12-30' => 'Rizal Day',
        
        // Variable Date Holidays (calculated)
        // Easter Sunday (changes every year)
        // Eid al-Fitr (changes every year)
        // Eid al-Adha (changes every year)
    ];
    
    // Add Easter Sunday (simplified calculation)
    $easter = date('Y-m-d', easter_date($year));
    $nationalHolidays[$easter] = 'Easter Sunday';
    
    // Add Good Friday (2 days before Easter)
    $goodFriday = date('Y-m-d', strtotime($easter . ' -2 days'));
    $nationalHolidays[$goodFriday] = 'Good Friday';
    
    // Add Maundy Thursday (3 days before Easter)
    $maundyThursday = date('Y-m-d', strtotime($easter . ' -3 days'));
    $nationalHolidays[$maundyThursday] = 'Maundy Thursday';
    
    // Add Black Saturday (1 day before Easter)
    $blackSaturday = date('Y-m-d', strtotime($easter . ' -1 day'));
    $nationalHolidays[$blackSaturday] = 'Black Saturday';
    
    // Add All Saints' Day (November 1)
    $nationalHolidays[$year . '-11-01'] = 'All Saints\' Day';
    
    // Add All Souls' Day (November 2)
    $nationalHolidays[$year . '-11-02'] = 'All Souls\' Day';
    
    // Add Christmas Eve (December 24)
    $nationalHolidays[$year . '-12-24'] = 'Christmas Eve';
    
    // Add New Year's Eve (December 31)
    $nationalHolidays[$year . '-12-31'] = 'New Year\'s Eve';
    
    // Convert to array format for easier handling
    $holidaysArray = [];
    foreach ($nationalHolidays as $date => $name) {
        $holidaysArray[] = [
            'date' => $date,
            'name' => $name,
            'type' => 'national'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'holidays' => $holidaysArray
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching national holidays: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching national holidays'
    ]);
}
?> 