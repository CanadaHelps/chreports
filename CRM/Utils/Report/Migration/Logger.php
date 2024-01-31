<?php

class CRM_Utils_Report_Migration_Logger {

    private $csvFilePath;
    protected $instance;

    public function __construct($csvFilePath) {
        $this->csvFilePath = $csvFilePath;
    }

    public function setInstance (string $instance) {
        $this->instance = $instance;
    }

    public function getInstance () {
        return $this->instance;
    }

    public function addStatus($logData, $isSuccess): bool {
        // Open the CSV file or create it if it doesn't exist
        $file = fopen($this->csvFilePath, 'a');

        if ($file) {
            // Prepare data for CSV
            $data = [
                $this->instance,
                $isSuccess ? 'Success' : 'Failed',
            ];

            // Add log Message to the last Column (if present)
            // Convert to string if it's an array
            if(isset($logData['logMessages'])) {
                if(!empty($logData['logMessages'])) {
                    if(is_array($logData['logMessages'])) {
                        $logData['logMessages'] = implode(" | ", $logData['logMessages']);
                    }
                }
            }

            $data = array_merge($data, array_values($logData));

            // Write to CSV
            fputcsv($file, $data);

            // Close the file
            fclose($file);

            return true; // Log success
        }
        return false; // Log failure
    }

    public function addStats ($stats): bool {
        if($stats) {
            $file = fopen($this->csvFilePath, 'a');
            if ($file) {
                fputcsv($file, []);
                $instance = $this->getInstance();
                if($instance) {
                    $data = [
                        "Charity: ", $instance
                    ];
                    fputcsv($file, $data);
                }
                $data = [
                    "Total Custom Reports:", $stats['total_custom_reports'], 
                    "Eligible for migration:", $stats['success'] + $stats['failed']
                ];
                fputcsv($file, $data);
                $data = [
                    "Success:", $stats['success'], 
                    "Failed:", $stats['failed']
                ];
                fputcsv($file, $data);
                fputcsv($file, []);
                return true;
            }
        }
        return false;
    }
}
?>