<?php

namespace Curricula\Command;

use Curricula\Service\AIService;
use Curricula\Service\CSVService;
use Curricula\Service\ConfigService;
use Curricula\Service\DirectoryService;
use Curricula\Service\ProcessorService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to run all CV processing steps in sequence
 */
#[AsCommand(
    name: 'cv:run-all',
    description: 'Run all CV processing steps in sequence',
    aliases: ['all']
)]
class RunAllCommand extends BaseCommand
{
    /**
     * Configure the command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setHelp('This command runs all CV processing steps in sequence: extract, process, and analyze')
            ->addOption(
                'job-requirements',
                'j',
                InputOption::VALUE_OPTIONAL,
                'Path to custom job requirements JSON file'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force processing of all files even if output files already exist'
            );
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Running all CV processing steps...</info>');
        
        // Get force option
        $force = $input->getOption('force');
        if ($force) {
            $output->writeln('<comment>Force mode enabled: Will reprocess all files</comment>');
        }
        
        // Check if a custom job requirements file was provided
        $jobRequirementsPath = $input->getOption('job-requirements');
        if ($jobRequirementsPath) {
            try {
                // Create a new config service with the custom job requirements
                $this->configService = new ConfigService($this->directoryService, $jobRequirementsPath);
                
                // Reinitialize dependent services
                $this->aiService = new AIService($this->configService);
                $this->csvService = new CSVService($this->directoryService, $this->configService);
                $this->processorService = new ProcessorService(
                    $this->textExtractionService,
                    $this->aiService,
                    $this->logService,
                    $this->directoryService,
                    $this->csvService
                );
                
                $output->writeln('<info>Using custom job requirements file: ' . $jobRequirementsPath . '</info>');
            } catch (\Throwable $e) {
                $output->writeln('<error>Error loading custom job requirements: ' . $e->getMessage() . '</error>');
                return self::FAILURE;
            }
        }
        
        try {
            // Step 1: Extract
            $output->writeln("\n<comment>=== STEP 1: EXTRACTION ===</comment>");
            $extractedFiles = $this->processorService->extract($force);
            
            if (empty($extractedFiles)) {
                $output->writeln('<error>No files were extracted. Check the input directory.</error>');
                return self::FAILURE;
            }
            
            // Step 2: Process
            $output->writeln("\n<comment>=== STEP 2: STANDARDIZATION ===</comment>");
            $processedFiles = $this->processorService->process(null, $force);
            
            if (empty($processedFiles)) {
                $output->writeln('<error>No files were processed. Check the extracted directory.</error>');
                return self::FAILURE;
            }
            
            // Step 3: Analyze
            $output->writeln("\n<comment>=== STEP 3: ANALYSIS ===</comment>");
            $reportPath = $this->processorService->analyze(null, $force);
            
            if (!$reportPath) {
                $output->writeln('<error>Failed to generate analysis report</error>');
                return self::FAILURE;
            }
            
            $output->writeln("\n<info>Processing completed successfully!</info>");
            $output->writeln("<info>Report generated at: " . $reportPath . "</info>");
            
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>Error during processing: ' . $e->getMessage() . '</error>');
            return self::FAILURE;
        }
    }
}
