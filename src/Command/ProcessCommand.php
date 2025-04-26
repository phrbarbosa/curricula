<?php

namespace Curricula\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to process and standardize extracted CV texts
 */
#[AsCommand(
    name: 'cv:process',
    description: 'Process and standardize extracted CV texts',
    aliases: ['process']
)]
class ProcessCommand extends BaseCommand
{
    /**
     * Configure the command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setHelp('This command processes and standardizes extracted CV texts')
            ->addArgument(
                'file',
                InputArgument::OPTIONAL,
                'Specific file to process (optional)'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force processing even if the output file already exists'
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
        $output->writeln('<info>Standardizing CV data...</info>');
        
        $force = $input->getOption('force');
        if ($force) {
            $output->writeln('<comment>Force mode enabled: Will reprocess all files</comment>');
        }
        
        try {
            $file = $input->getArgument('file');
            $files = null;
            
            if ($file) {
                $extractedDir = $this->directoryService->getDirectoryPath('extracted');
                $filePath = $extractedDir . '/' . $file;
                
                if (!file_exists($filePath)) {
                    $output->writeln('<error>File not found: ' . $file . '</error>');
                    return self::FAILURE;
                }
                
                $files = [$filePath];
                $output->writeln('<info>Processing specific file: ' . $file . '</info>');
            }
            
            $processedFiles = $this->processorService->process($files, $force);
            
            if (count($processedFiles) > 0) {
                $output->writeln('<info>Successfully processed ' . count($processedFiles) . ' files</info>');
                return self::SUCCESS;
            } else {
                $output->writeln('<comment>No files were processed. Check the extracted directory or use --force to reprocess existing files.</comment>');
                return self::FAILURE;
            }
        } catch (\Throwable $e) {
            $output->writeln('<error>Error during processing: ' . $e->getMessage() . '</error>');
            return self::FAILURE;
        }
    }
}
