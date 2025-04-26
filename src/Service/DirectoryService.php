<?php

namespace Curricula\Service;

/**
 * Service responsible for managing directories in the application
 */
class DirectoryService
{
    /**
     * Base path of the application
     *
     * @var string
     */
    private string $basePath;

    /**
     * Directory configuration from job requirements
     *
     * @var array|null
     */
    private ?array $directoryConfig = null;

    /**
     * DirectoryService constructor
     */
    public function __construct()
    {
        $this->basePath = dirname(__DIR__, 2);
        $this->loadDirectoryConfig();
    }

    /**
     * Load directory configuration from job requirements file
     *
     * @return void
     */
    private function loadDirectoryConfig(): void
    {
        $jobRequirementsPath = $this->basePath . '/config/job-requirements.json';
        if (file_exists($jobRequirementsPath)) {
            $jobRequirements = json_decode(file_get_contents($jobRequirementsPath), true);
            if (isset($jobRequirements['directories']) && is_array($jobRequirements['directories'])) {
                $this->directoryConfig = $jobRequirements['directories'];
            }
        }
        
        // If no directory config found, use defaults
        if ($this->directoryConfig === null) {
            $this->directoryConfig = [
                'input' => 'input',
                'extracted' => 'extracted',
                'processed' => 'processed',
                'analysis' => 'analysis',
                'report' => 'report',
                'log' => 'log',
                'temp' => 'temp'
            ];
        }
    }

    /**
     * Ensure all required directories exist
     *
     * @return void
     */
    public function ensureDirectoriesExist(): void
    {
        $dirs = [
            $this->getDirectoryPath('input'),
            $this->getDirectoryPath('extracted'),
            $this->getDirectoryPath('processed'),
            $this->getDirectoryPath('analysis'),
            $this->getDirectoryPath('report'),
            $this->getDirectoryPath('log'),
            $this->getDirectoryPath('log') . '/extract',
            $this->getDirectoryPath('log') . '/process',
            $this->getDirectoryPath('log') . '/analyze',
            $this->getDirectoryPath('temp'),
        ];

        foreach ($dirs as $dir) {
            $this->ensureDirectoryExists($dir);
        }
    }

    /**
     * Ensure a specific directory exists
     *
     * @param string $dir Directory path
     * @return bool True if directory exists or was created successfully
     */
    public function ensureDirectoryExists(string $dir): bool
    {
        if (!file_exists($dir)) {
            return mkdir($dir, 0777, true);
        }
        return is_dir($dir);
    }

    /**
     * Get the path for a specific directory type
     *
     * @param string $type Directory type (input, extracted, processed, etc.)
     * @return string Full path to the directory
     */
    public function getDirectoryPath(string $type): string
    {
        $dirName = $this->directoryConfig[$type] ?? $type;
        return $this->basePath . '/' . $dirName;
    }

    /**
     * Get the base path of the application
     *
     * @return string Base path
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Reload directory configuration
     * Used when a custom job requirements file is specified
     *
     * @param string|null $jobRequirementsPath Custom path to job requirements file
     * @return void
     */
    public function reloadDirectoryConfig(?string $jobRequirementsPath = null): void
    {
        if ($jobRequirementsPath !== null && file_exists($jobRequirementsPath)) {
            $jobRequirements = json_decode(file_get_contents($jobRequirementsPath), true);
            if (isset($jobRequirements['directories']) && is_array($jobRequirements['directories'])) {
                $this->directoryConfig = $jobRequirements['directories'];
                return;
            }
        }
        
        // If no custom path or invalid file, load from default path
        $this->loadDirectoryConfig();
    }
}
