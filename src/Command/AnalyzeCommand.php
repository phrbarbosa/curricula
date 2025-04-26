<?php

namespace Curricula\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to analyze standardized CVs
 */
#[AsCommand(
    name: 'cv:analyze',
    description: 'Analyze standardized CVs',
    aliases: ['analyze']
)]
class AnalyzeCommand extends BaseCommand
{
    /**
     * Configure the command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setHelp('This command analyzes standardized CVs')
            ->addArgument(
                'file',
                InputArgument::OPTIONAL,
                'Specific file to analyze (optional)'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force analysis even if the output file already exists'
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
        $output->writeln('<info>Analyzing CV data...</info>');
        
        $force = $input->getOption('force');
        if ($force) {
            $output->writeln('<comment>Force mode enabled: Will reanalyze all files</comment>');
        }
        
        try {
            $file = $input->getArgument('file');
            $files = null;
            
            if ($file) {
                $processedDir = $this->directoryService->getDirectoryPath('processed');
                $filePath = $processedDir . '/' . $file;
                
                if (!file_exists($filePath)) {
                    $output->writeln('<error>File not found: ' . $file . '</error>');
                    return self::FAILURE;
                }
                
                $files = [$filePath];
                $output->writeln('<info>Analyzing specific file: ' . $file . '</info>');
            }
            
            $reportPath = $this->processorService->analyze($files, $force);
            
            if ($reportPath) {
                $output->writeln('<info>Analysis completed successfully</info>');
                $output->writeln('<info>Report generated at: ' . $reportPath . '</info>');
                return self::SUCCESS;
            } else {
                $output->writeln('<error>Failed to generate analysis report. Check the processed directory or use --force to reanalyze existing files.</error>');
                return self::FAILURE;
            }
        } catch (\Throwable $e) {
            $output->writeln('<error>Error during analysis: ' . $e->getMessage() . '</error>');
            return self::FAILURE;
        }
    }
}
