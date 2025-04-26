<?php

namespace Curricula\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to extract text from CVs
 */
#[AsCommand(
    name: 'cv:extract',
    description: 'Extract text from all CVs in the input directory',
    aliases: ['extract']
)]
class ExtractCommand extends BaseCommand
{
    /**
     * Configure the command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setHelp('This command extracts text from all CVs in the input directory')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force extraction even if the output file already exists'
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
        $output->writeln('<info>Extracting text from CVs...</info>');
        
        $force = $input->getOption('force');
        if ($force) {
            $output->writeln('<comment>Force mode enabled: Will re-extract all files</comment>');
        }
        
        try {
            $processedFiles = $this->processorService->extract($force);
            
            if (count($processedFiles) > 0) {
                $output->writeln('<info>Successfully extracted text from ' . count($processedFiles) . ' files</info>');
                return self::SUCCESS;
            } else {
                $output->writeln('<comment>No files were processed. Check the input directory or use --force to reprocess existing files.</comment>');
                return self::FAILURE;
            }
        } catch (\Throwable $e) {
            $output->writeln('<error>Error during extraction: ' . $e->getMessage() . '</error>');
            return self::FAILURE;
        }
    }
}
