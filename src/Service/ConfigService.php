<?php

namespace Curricula\Service;

use Aws\BedrockRuntime\BedrockRuntimeClient;

/**
 * Service responsible for managing application configuration
 */
class ConfigService
{
    /**
     * AWS Bedrock client
     *
     * @var BedrockRuntimeClient
     */
    private BedrockRuntimeClient $bedrockClient;

    /**
     * Job requirements data
     *
     * @var array
     */
    private array $jobRequirements;

    /**
     * Output language
     *
     * @var string
     */
    private string $outputLanguage;

    /**
     * Model ID for AI service
     *
     * @var string
     */
    private string $modelId;

    /**
     * Directory service
     *
     * @var DirectoryService
     */
    private DirectoryService $directoryService;

    /**
     * ConfigService constructor
     *
     * @param DirectoryService $directoryService
     * @param string|null $jobRequirementsPath Custom path to job requirements file
     */
    public function __construct(DirectoryService $directoryService, ?string $jobRequirementsPath = null)
    {
        $this->directoryService = $directoryService;

        // Create AWS Bedrock client
        $this->bedrockClient = new BedrockRuntimeClient([
            'credentials' => [
                'key'    => $_ENV['AWS_ACCESS_KEY'],
                'secret' => $_ENV['AWS_SECRET_KEY']
            ],
            'region' => $_ENV['AWS_REGION'],
            'version' => $_ENV['AWS_VERSION']
        ]);

        $this->modelId = $_ENV['AWS_MODEL_ID'];

        // Load job requirements
        $defaultPath = $this->directoryService->getBasePath() . '/config/job-requirements.json';
        $jobRequirementsPath = $jobRequirementsPath ?? $defaultPath;
        
        $this->loadJobRequirements($jobRequirementsPath);
        
        // Reload directory configuration if a custom job requirements file is used
        if ($jobRequirementsPath !== $defaultPath) {
            $this->directoryService->reloadDirectoryConfig($jobRequirementsPath);
        }
    }

    /**
     * Load job requirements from the specified file
     *
     * @param string $path Path to job requirements JSON file
     * @return void
     * @throws \RuntimeException If the file cannot be read or parsed
     */
    public function loadJobRequirements(string $path): void
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Job requirements file not found: $path");
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException("Failed to read job requirements file: $path");
        }

        $this->jobRequirements = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in job requirements file: " . json_last_error_msg());
        }

        $this->outputLanguage = $this->jobRequirements['output_language'];
    }

    /**
     * Get the AWS Bedrock client
     *
     * @return BedrockRuntimeClient
     */
    public function getBedrockClient(): BedrockRuntimeClient
    {
        return $this->bedrockClient;
    }

    /**
     * Get job requirements
     *
     * @return array
     */
    public function getJobRequirements(): array
    {
        return $this->jobRequirements;
    }

    /**
     * Get output language
     *
     * @return string
     */
    public function getOutputLanguage(): string
    {
        return $this->outputLanguage;
    }

    /**
     * Get model ID
     *
     * @return string
     */
    public function getModelId(): string
    {
        return $this->modelId;
    }
}
