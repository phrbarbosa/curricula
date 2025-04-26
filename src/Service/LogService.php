<?php

namespace Curricula\Service;

use DateTime;

/**
 * Service responsible for logging operations
 */
class LogService
{
    /**
     * Directory service
     *
     * @var DirectoryService
     */
    private DirectoryService $directoryService;

    /**
     * LogService constructor
     *
     * @param DirectoryService $directoryService
     */
    public function __construct(DirectoryService $directoryService)
    {
        $this->directoryService = $directoryService;
    }

    /**
     * Log a message
     *
     * @param string $stage Processing stage (extract, process, analyze)
     * @param string $file File being processed
     * @param string $message Message to log
     * @param string $type Log type (error|info)
     * @return void
     */
    public function logMessage(string $stage, string $file, string $message, string $type = 'error'): void
    {
        $logPath = $this->directoryService->getDirectoryPath('log');
        $this->directoryService->ensureDirectoryExists($logPath . '/' . $stage);
        
        $timestamp = (new DateTime())->format('Y-m-d H:i:s');
        $logMessage = sprintf("[%s] File: %s\n%s: %s\n\n", $timestamp, $file, ucfirst($type), $message);
        $logFile = $logPath . '/' . $stage . '/' . $type . '_' . date('Y-m-d') . '.log';
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Log an error
     *
     * @param string $stage Processing stage
     * @param string $file File being processed
     * @param string $error Error message
     * @return void
     */
    public function logError(string $stage, string $file, string $error): void
    {
        $this->logMessage($stage, $file, $error, 'error');
    }

    /**
     * Log information
     *
     * @param string $stage Processing stage
     * @param string $file File being processed
     * @param string $info Information message
     * @return void
     */
    public function logInfo(string $stage, string $file, string $info): void
    {
        $this->logMessage($stage, $file, $info, 'info');
    }
}
