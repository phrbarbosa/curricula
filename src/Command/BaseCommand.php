<?php

namespace Curricula\Command;

use Curricula\Service\AIService;
use Curricula\Service\CSVService;
use Curricula\Service\ConfigService;
use Curricula\Service\DirectoryService;
use Curricula\Service\LogService;
use Curricula\Service\ProcessorService;
use Curricula\Service\TextExtractionService;
use Symfony\Component\Console\Command\Command;

/**
 * Base command class with common services and functionality
 */
abstract class BaseCommand extends Command
{
    /**
     * Directory service
     *
     * @var DirectoryService
     */
    protected DirectoryService $directoryService;

    /**
     * Config service
     *
     * @var ConfigService
     */
    protected ConfigService $configService;

    /**
     * Log service
     *
     * @var LogService
     */
    protected LogService $logService;

    /**
     * Text extraction service
     *
     * @var TextExtractionService
     */
    protected TextExtractionService $textExtractionService;

    /**
     * AI service
     *
     * @var AIService
     */
    protected AIService $aiService;

    /**
     * CSV service
     *
     * @var CSVService
     */
    protected CSVService $csvService;

    /**
     * Processor service
     *
     * @var ProcessorService
     */
    protected ProcessorService $processorService;

    /**
     * BaseCommand constructor
     *
     * @param string|null $name Command name
     */
    public function __construct(string $name = null)
    {
        parent::__construct($name);
        
        // Initialize services
        $this->directoryService = new DirectoryService();
        $this->configService = new ConfigService($this->directoryService);
        $this->logService = new LogService($this->directoryService);
        $this->textExtractionService = new TextExtractionService($this->logService, $this->directoryService, $this->configService);
        $this->aiService = new AIService($this->configService);
        $this->csvService = new CSVService($this->directoryService, $this->configService);
        $this->processorService = new ProcessorService(
            $this->textExtractionService,
            $this->aiService,
            $this->logService,
            $this->directoryService,
            $this->csvService
        );
    }
}
