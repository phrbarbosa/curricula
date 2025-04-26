<?php

namespace Curricula\Service;

use Exception;
use Smalot\PdfParser\Parser;
use Smalot\PdfParser\Config;

/**
 * Service responsible for extracting text from documents
 */
class TextExtractionService
{
    /**
     * PDF Parser
     *
     * @var Parser
     */
    private Parser $pdfParser;

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
     * Config service
     *
     * @var ConfigService
     */
    private ConfigService $configService;

    /**
     * Whether Tesseract OCR is available
     *
     * @var bool
     */
    private bool $tesseractAvailable;

    /**
     * TextExtractionService constructor
     *
     * @param LogService $logService
     * @param DirectoryService $directoryService
     * @param ConfigService $configService
     */
    public function __construct(
        LogService $logService,
        DirectoryService $directoryService,
        ConfigService $configService
    ) {
        $this->logService = $logService;
        $this->directoryService = $directoryService;
        $this->configService = $configService;

        // Configure PDF Parser
        $configPdfParser = new Config();
        $configPdfParser->setRetainImageContent(false);
        $configPdfParser->setDecodeMemoryLimit(100000000);
        $this->pdfParser = new Parser([], $configPdfParser);

        // Check if Tesseract OCR is available
        $this->tesseractAvailable = $this->checkTesseractAvailability();
    }

    /**
     * Extract text from a file
     *
     * @param string $file Path to file
     * @return string Extracted text
     */
    public function extractText(string $file): string
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            // Register a custom error handler to catch memory limit errors
            $previousErrorHandler = set_error_handler(function($errno, $errstr, $errfile, $errline) use ($file, &$previousErrorHandler) {
                // Check if it's a memory limit error
                if (strpos($errstr, 'memory') !== false && strpos($errstr, 'exhausted') !== false) {
                    $this->logService->logError('extract', $file, 'Memory limit exceeded: ' . $errstr);
                    // Throw a catchable exception
                    throw new \Exception('Memory limit exceeded while processing PDF: ' . basename($file));
                }
                
                // For other errors, call the previous error handler
                if ($previousErrorHandler) {
                    return call_user_func($previousErrorHandler, $errno, $errstr, $errfile, $errline);
                }
                
                // Default error handler behavior
                return false;
            });
            
            try {
                // Increase memory limit temporarily for this operation
                $originalMemoryLimit = ini_get('memory_limit');
                ini_set('memory_limit', '512M'); // Increase to 512MB
                
                // Extract text from PDF
                $pdf = $this->pdfParser->parseFile($file);
                $text = $pdf->getText();
                $pdf = null; // Release memory
                
                // Restore original memory limit
                ini_set('memory_limit', $originalMemoryLimit);
                
                // Restore previous error handler
                restore_error_handler();
                
                return $text;
            } catch (\Exception $e) {
                // Restore error handler in case of exception
                restore_error_handler();
                
                // Rethrow the exception to be caught by the caller
                throw $e;
            }
        } else {
            // Implement other extensions if needed
            return '';
        }
    }

    /**
     * Extract text using OCR as fallback
     *
     * @param string $file Path to file
     * @return string Extracted text
     */
    public function extractTextWithOCR(string $file): string
    {
        try {
            // Check if the library is available
            if (!class_exists('thiagoalessio\\TesseractOCR\\TesseractOCR')) {
                throw new Exception('Tesseract OCR PHP library is not available');
            }

            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            if ($extension === 'pdf') {
                // Prepare OCR
                $langParam = strtolower($this->configService->getOutputLanguage()) === 'pt-br' ? 'por' : 'eng';

                // Use thiagoalessio/tesseract_ocr library
                $ocr = new \thiagoalessio\TesseractOCR\TesseractOCR($file);
                $ocr->lang($langParam);

                // Additional configurations to improve OCR
                try {
                    // $ocr->psm(6); // Assume a single uniform block of text
                } catch (Exception $e) {
                    // Ignore configuration errors, continue with default settings
                }

                // Extract text
                $text = $ocr->run();

                if (empty(trim($text))) {
                    // Try extracting again with different configuration if text is empty
                    try {
                        $ocr = new \thiagoalessio\TesseractOCR\TesseractOCR($file);
                        $ocr->lang($langParam);
                        $ocr->psm(3); // Assume text with column
                        $text = $ocr->run();
                    } catch (Exception $e) {
                        // Ignore error and keep text empty
                    }
                }

                return $text;
            } else {
                // For other document formats
                throw new Exception('OCR not implemented for format: ' . $extension);
            }
        } catch (Exception $e) {
            // Log error and return empty text
            $this->logService->logError('extract', $file, 'OCR Error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Save extracted text to file
     *
     * @param string $originalFile Original file path
     * @param string $text Extracted text
     * @return string Path to saved file
     */
    public function saveExtractedText(string $originalFile, string $text): string
    {
        $extractedDir = $this->directoryService->getDirectoryPath('extracted');
        $this->directoryService->ensureDirectoryExists($extractedDir);
        
        $filename = basename($originalFile, '.' . pathinfo($originalFile, PATHINFO_EXTENSION)) . '.txt';
        $outputPath = $extractedDir . '/' . $filename;
        
        file_put_contents($outputPath, $text);
        
        return $outputPath;
    }

    /**
     * Check if Tesseract OCR is available
     *
     * @return bool True if Tesseract OCR is available
     */
    private function checkTesseractAvailability(): bool
    {
        // First check if the PHP class exists
        $classExists = class_exists('thiagoalessio\\TesseractOCR\\TesseractOCR');

        if (!$classExists) {
            return false;
        }

        // Then try to execute tesseract to check if it's working
        try {
            $ocr = new \thiagoalessio\TesseractOCR\TesseractOCR();
            $version = $ocr->version();
            return true;
        } catch (Exception $e) {
            $this->logService->logError('system', 'tesseract', 'OCR not available: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if Tesseract OCR is available
     *
     * @return bool
     */
    public function isTesseractAvailable(): bool
    {
        return $this->tesseractAvailable;
    }
}
