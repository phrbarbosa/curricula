<?php

namespace Curricula\Service;

use League\Csv\Writer;

/**
 * Service responsible for CSV operations
 */
class CSVService
{
    /**
     * CSV writer instance
     *
     * @var Writer|null
     */
    private ?Writer $csvWriter = null;

    /**
     * CSV file path
     * 
     * @var string|null
     */
    private ?string $csvFilePath = null;

    /**
     * Directory service
     *
     * @var DirectoryService
     */
    private DirectoryService $directoryService;

    /**
     * Config service
     *
     * @var ConfigService
     */
    private ConfigService $configService;

    /**
     * CSVService constructor
     *
     * @param DirectoryService $directoryService
     * @param ConfigService $configService
     */
    public function __construct(DirectoryService $directoryService, ConfigService $configService)
    {
        $this->directoryService = $directoryService;
        $this->configService = $configService;
        
        // Initialize CSV file and writer
        $this->initializeCSVWriter();
    }

    /**
     * Initialize CSV writer with proper settings
     *
     * @return void
     */
    private function initializeCSVWriter(): void
    {
        $reportDir = $this->directoryService->getDirectoryPath('report');
        $this->directoryService->ensureDirectoryExists($reportDir);
        
        $this->csvFilePath = $reportDir . '/consolidated_analysis_' . date('Y-m-d') . '.csv';
        
        // Check if file already exists
        $fileExists = file_exists($this->csvFilePath);
        
        // Create CSV writer with semicolon delimiter
        $this->csvWriter = Writer::createFromPath($this->csvFilePath, 'a+'); // Append mode
        $this->csvWriter->setDelimiter(';');
        
        // If file doesn't exist yet, write headers
        if (!$fileExists) {
            $headers = [
                'File',
                'Name',
                'Email',
                'Incomplete Info',
                'Education',
                'Key Skills',
                'Score',
                'Sentiment'
            ];
            
            // Convert headers to ANSI encoding
            $headers = array_map(function($header) {
                return mb_convert_encoding($header, 'Windows-1252', 'UTF-8');
            }, $headers);
            
            $this->csvWriter->insertOne($headers);
        }
    }

    /**
     * Add data to CSV file immediately
     *
     * @param string $file File path
     * @param string $standardizedCV Standardized CV text
     * @param array $analysis Analysis data
     * @return void
     */
    public function addToCSVData(string $file, string $standardizedCV, array $analysis): void
    {
        $csvData = $analysis['csvData'];

        $row = [
            'file' => basename($file),
            'name' => $csvData['name'],
            'email' => $csvData['email'],
            'education' => $csvData['education'],
            'key_skills' => $csvData['key_skills'],
            'score' => $csvData['score'],
            'sentiment' => $csvData['sentiment'],
            'incomplete_info' => $csvData['incomplete_info']
        ];
        
        // Convert all data to ANSI encoding
        $row = array_map(function($value) {
            return mb_convert_encoding($value, 'Windows-1252', 'UTF-8');
        }, $row);
        
        // Insert row immediately into CSV file
        $this->csvWriter->insertOne([
            $row['file'],
            $row['name'],
            $row['email'],
            $row['incomplete_info'],
            $row['education'],
            $row['key_skills'],
            $row['score'],
            $row['sentiment']
        ]);
    }

    /**
     * Generate CSV report - now just returns the path to the already created file
     *
     * @return string Path to generated report
     */
    public function generateCSVReport(): string
    {
        // The file is already being written incrementally, so we just return the path
        return $this->csvFilePath;
    }
}
