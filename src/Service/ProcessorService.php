<?php

namespace Curricula\Service;

/**
 * Service responsible for coordinating CV processing workflow
 */
class ProcessorService
{
    /**
     * Text extraction service
     *
     * @var TextExtractionService
     */
    private TextExtractionService $textExtractionService;

    /**
     * AI service
     *
     * @var AIService
     */
    private AIService $aiService;

    /**
     * Log service
     *
     * @var LogService
     */
    private LogService $logService;

    /**
     * Directory service
     *
     * @var DirectoryService
     */
    private DirectoryService $directoryService;

    /**
     * CSV service
     *
     * @var CSVService
     */
    private CSVService $csvService;

    /**
     * ProcessorService constructor
     *
     * @param TextExtractionService $textExtractionService
     * @param AIService $aiService
     * @param LogService $logService
     * @param DirectoryService $directoryService
     * @param CSVService $csvService
     */
    public function __construct(
        TextExtractionService $textExtractionService,
        AIService $aiService,
        LogService $logService,
        DirectoryService $directoryService,
        CSVService $csvService
    ) {
        $this->textExtractionService = $textExtractionService;
        $this->aiService = $aiService;
        $this->logService = $logService;
        $this->directoryService = $directoryService;
        $this->csvService = $csvService;
    }

    /**
     * Extract text from all CVs in the input directory
     *
     * @param bool $force Force extraction even if output file exists
     * @return array List of processed files
     */
    public function extract(bool $force = false): array
    {
        $inputDir = $this->directoryService->getDirectoryPath('input');
        $extractedDir = $this->directoryService->getDirectoryPath('extracted');
        $files = glob($inputDir . '/*.[pP][dD][fF]') +
                 glob($inputDir . '/*.[dD][oO][cC][xX]');

        // Check OCR availability at the start of processing
        $ocrStatus = $this->textExtractionService->isTesseractAvailable() ?
            "✓ OCR available as fallback for problematic PDFs" :
            "! OCR not available. Problematic files may fail extraction";
        echo $ocrStatus . "\n\n";

        $processedFiles = [];
        foreach ($files as $file) {
            try {
                $filename = basename($file, '.' . pathinfo($file, PATHINFO_EXTENSION)) . '.txt';
                $outputPath = $extractedDir . '/' . $filename;
                
                // Skip existing files unless force is true
                if (!$force && file_exists($outputPath)) {
                    echo "- Skipping " . basename($file) . " (already extracted)\n";
                    $processedFiles[] = $outputPath;
                    continue;
                }
                
                echo "- Extracting data from CV: " . basename($file) . "\n";
                $text = $this->textExtractionService->extractText($file);

                // Check if extracted text is empty
                if (empty(trim($text))) {
                    echo "  ! Empty extracted text. Trying OCR as fallback...\n";
                    $this->logService->logInfo('extract', $file, 'Empty text, using OCR as fallback');

                    // Try extracting text using OCR
                    $text = $this->textExtractionService->extractTextWithOCR($file);

                    // If still empty after OCR, log error
                    if (empty(trim($text))) {
                        $this->logService->logError('extract', $file, 'Extracted text is empty, even after OCR attempt');
                        echo "  ! Warning: Failed to extract text with OCR from file " . basename($file) . "\n";
                        continue;
                    }

                    $this->logService->logInfo('extract', $file, 'OCR extraction successful');
                    echo "  ✓ Text successfully extracted using OCR\n";
                }

                $outputPath = $this->textExtractionService->saveExtractedText($file, $text);
                $processedFiles[] = $outputPath;
                echo "  ✓ Extraction completed successfully\n";
            } catch (\Throwable $e) {
                $this->logService->logError('extract', $file, $e->getMessage());
                echo "  ! Error processing " . basename($file) . ": " . $e->getMessage() . "\n";

                // Try extracting with OCR if there's an exception
                try {
                    echo "  ! Trying OCR as fallback after error...\n";
                    $this->logService->logInfo('extract', $file, 'Error in normal extraction, trying OCR: ' . $e->getMessage());

                    $text = $this->textExtractionService->extractTextWithOCR($file);

                    if (!empty(trim($text))) {
                        $outputPath = $this->textExtractionService->saveExtractedText($file, $text);
                        $processedFiles[] = $outputPath;
                        $this->logService->logInfo('extract', $file, 'OCR recovery successful after failure');
                        echo "  ✓ OCR extraction completed successfully after initial failure\n";
                    } else {
                        echo "  ! OCR extraction failed\n";
                    }
                } catch (\Throwable $ocrError) {
                    $this->logService->logError('extract', $file, 'OCR Error: ' . $ocrError->getMessage());
                    echo "  ! Error processing OCR on " . basename($file) . ": " . $ocrError->getMessage() . "\n";
                }
            }
        }
        return $processedFiles;
    }

    /**
     * Process and standardize extracted CV texts
     *
     * @param array|null $files Specific files to process, or null for all
     * @param bool $force Force processing even if output file exists
     * @return array List of processed files
     */
    public function process(?array $files = null, bool $force = false): array
    {
        if ($files === null) {
            $extractedDir = $this->directoryService->getDirectoryPath('extracted');
            $files = glob($extractedDir . '/*.txt');
        }

        $processedDir = $this->directoryService->getDirectoryPath('processed');
        $processedFiles = [];
        foreach ($files as $file) {
            try {
                $filename = basename($file, '.txt') . '_standardized.txt';
                $outputPath = $processedDir . '/' . $filename;
                
                // Skip existing files unless force is true
                if (!$force && file_exists($outputPath)) {
                    echo "- Skipping " . basename($file) . " (already processed)\n";
                    $processedFiles[] = $outputPath;
                    continue;
                }
                
                echo "- Standardizing CV data: " . basename($file) . "\n";
                $text = file_get_contents($file);

                if (empty(trim($text))) {
                    $this->logService->logError('process', $file, 'Text file is empty');
                    echo "  ! Warning: Empty file " . basename($file) . "\n";
                    continue;
                }

                $standardizedCV = $this->aiService->standardizeCV($text);

                if (empty(trim($standardizedCV))) {
                    $this->logService->logError('process', $file, 'Standardization returned empty text');
                    echo "  ! Warning: Standardization failed for " . basename($file) . "\n";
                    continue;
                }

                $outputPath = $this->saveStandardizedCV($file, $standardizedCV);
                $processedFiles[] = $outputPath;
                echo "  ✓ Standardization completed successfully\n";
            } catch (\Throwable $e) {
                $this->logService->logError('process', $file, $e->getMessage());
                echo "  ! Error standardizing " . basename($file) . ": " . $e->getMessage() . "\n";
            }
        }
        return $processedFiles;
    }

    /**
     * Analyze standardized CVs
     *
     * @param array|null $files Specific files to analyze, or null for all
     * @param bool $force Force analysis even if output file exists
     * @return string|bool Path to generated report or false on failure
     */
    public function analyze(?array $files = null, bool $force = false): string|bool
    {
        if ($files === null) {
            $processedDir = $this->directoryService->getDirectoryPath('processed');
            $files = glob($processedDir . '/*standardized.txt');
        }

        $analysisDir = $this->directoryService->getDirectoryPath('analysis');
        $analyzedFiles = [];
        $reportPath = null;
        
        foreach ($files as $file) {
            try {
                $filename = str_replace('_standardized.txt', '_analysis.txt', basename($file));
                $outputPath = $analysisDir . '/' . $filename;
                
                // Skip existing files unless force is true
                if (!$force && file_exists($outputPath)) {
                    echo "- Skipping " . basename($file) . " (already analyzed)\n";
                    $analyzedFiles[] = $outputPath;
                    continue;
                }
                
                echo "- Analyzing CV data: " . basename($file) . "\n";
                $standardizedCV = file_get_contents($file);

                if (empty(trim($standardizedCV))) {
                    $this->logService->logError('analyze', $file, 'Standardized file is empty');
                    echo "  ! Warning: Empty file " . basename($file) . "\n";
                    continue;
                }

                $analysisData = $this->aiService->analyzeCV($standardizedCV);
//var_dump($analysisData);
                if (!isset($analysisData['report']) || empty(trim($analysisData['report']))) {
                    $this->logService->logError('analyze', $file, 'Analysis returned invalid data');
                    echo "  ! Warning: Analysis failed for " . basename($file) . "\n";
                    continue;
                }

                $this->saveAnalysis($file, $analysisData);
                
                // Add to CSV file immediately after analysis
                $this->csvService->addToCSVData($file, $standardizedCV, $analysisData);
                
                $analyzedFiles[] = $outputPath;
                echo "  ✓ Analysis completed successfully\n";
                
                // Get the report path after the first successful analysis
                if ($reportPath === null) {
                    $reportPath = $this->csvService->generateCSVReport();
                }
            } catch (\Throwable $e) {
                $this->logService->logError('analyze', $file, $e->getMessage());
                echo "  ! Error analyzing " . basename($file) . ": " . $e->getMessage() . "\n";
            }
        }

        // If no files were analyzed and we don't have a report path yet, get it now
        if (empty($analyzedFiles) && $reportPath === null) {
            return false;
        } else if ($reportPath === null) {
            $reportPath = $this->csvService->generateCSVReport();
        }
        
        return $reportPath;
    }

    /**
     * Save standardized CV to file
     *
     * @param string $originalFile Original file path
     * @param string $standardizedCV Standardized CV text
     * @return string Path to saved file
     */
    private function saveStandardizedCV(string $originalFile, string $standardizedCV): string
    {
        $processedDir = $this->directoryService->getDirectoryPath('processed');
        $this->directoryService->ensureDirectoryExists($processedDir);
        
        $filename = basename($originalFile, '.txt') . '_standardized.txt';
        $outputPath = $processedDir . '/' . $filename;
        
        file_put_contents($outputPath, $standardizedCV);
        
        return $outputPath;
    }

    /**
     * Save analysis to file
     *
     * @param string $originalFile Original file path
     * @param array $analysis Analysis data
     * @return string Path to saved file
     */
    private function saveAnalysis(string $originalFile, array $analysis): string
    {
        $analysisDir = $this->directoryService->getDirectoryPath('analysis');
        $this->directoryService->ensureDirectoryExists($analysisDir);
        
        $filename = str_replace('_standardized.txt', '_analysis.txt', basename($originalFile));
        $outputPath = $analysisDir . '/' . $filename;
        
        file_put_contents($outputPath, $analysis['report']);
        
        return $outputPath;
    }
}
