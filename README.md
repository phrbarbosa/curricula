# Curricula - CV Analysis Pipeline

This pipeline processes resumes in PDF and DOCX formats using AWS Bedrock and Claude to analyze and score candidates based on job requirements.

## Structure
- `input/` - Place PDF and DOCX files here
- `extracted/` - Contains text extracted from documents
- `processed/` - Contains standardized resumes
- `analysis/` - Contains individual analyses
- `report/` - Contains the final CSV report
- `log/` - Error and warning logs
  - `log/extract/` - Logs from the extraction step
  - `log/process/` - Logs from the standardization step
  - `log/analyze/` - Logs from the analysis step
- `config/` - Configuration files
- `src/` - Source code
- `bin/` - Executable scripts

## Requirements
- PHP 8.0+
- AWS SDK for PHP
- Composer
- PDF and DOCX processing libraries

## Configuration
1. Install dependencies: `composer install`
2. Copy the `.env.example` file to `.env` and configure your AWS credentials:
   ```bash
   cp .env.example .env
   ```
3. Configure job requirements in `config/job-requirements.json`:
   - Define the job description and criteria
   - Configure the output language in `output_language` ("pt-BR", "en-US", etc)
   - Configure working directories in the `directories` section

## Usage

The system can be run in three separate steps or all at once:

### Run all steps
```bash
php bin/console cv:run-all
```

You can also specify a custom requirements file:
```bash
php bin/console cv:run-all --job-requirements=path/to/requirements.json
```

To force reprocessing of already processed files:
```bash
php bin/console cv:run-all --force
```

### Run individual steps

1. Extract text from documents:
```bash
php bin/console cv:extract
# Or force re-extraction of all files:
php bin/console cv:extract --force
```

2. Standardize resumes:
```bash
php bin/console cv:process
# Or process a specific file:
php bin/console cv:process file.txt
# Or force reprocessing:
php bin/console cv:process --force
```

3. Analyze resumes:
```bash
php bin/console cv:analyze
# Or analyze a specific file:
php bin/console cv:analyze file_standardized.txt
# Or force reanalysis:
php bin/console cv:analyze --force
```

### Help
To see all available commands:
```bash
php bin/console
```

To see help for a specific command:
```bash
php bin/console help cv:extract
```

## Results
- **Extraction**: 
  - Extracted text files are saved in `extracted/`
  - Empty or error resumes are logged in `log/extract/errors_DATE.log`

- **Processing**: 
  - Standardized resumes are saved in `processed/` with suffix `_standardized.txt`
  - Standardization errors are logged in `log/process/errors_DATE.log`

- **Analysis**: 
  - Individual analyses are saved in `analysis/` with suffix `_analysis.txt`
  - The final CSV report is generated in `report/consolidated_analysis_DATE.csv` with semicolon separators and ANSI encoding
  - Analysis errors are logged in `log/analyze/errors_DATE.log`

## Error Handling
The system has robust error handling at each stage:

1. **Extraction**:
   - Files that cannot be read
   - Corrupted PDFs or memory issues
   - Documents without extractable text
   - Errors are logged and the file doesn't advance to the next stage
   - Automatic attempt to use OCR as a fallback for problematic PDFs

2. **Processing**:
   - Empty text files
   - Standardization failures
   - Communication errors with the model
   - Files with errors don't advance to analysis

3. **Analysis**:
   - Poorly formatted resumes
   - Model analysis failures
   - Invalid or incomplete data
   - Each analysis is saved to CSV immediately after completion

All errors are logged with timestamps in the corresponding log files, allowing tracking and diagnosis.

## Customization

### Directories
The directories used by the system are configured in the `config/job-requirements.json` file in the `directories` section:

```json
"directories": {
  "input": "input",
  "extracted": "extracted",
  "processed": "processed",
  "analysis": "analysis",
  "report": "report",
  "log": "log",
  "temp": "temp"
}
```

### AWS Configuration
AWS credentials and settings are defined in the `.env` file:

```
AWS_ACCESS_KEY=your_access_key
AWS_SECRET_KEY=your_secret_key
AWS_REGION=us-east-1
AWS_MODEL_ID=anthropic.claude-3-5-sonnet-20240620-v1:0
```

## Additional Features

- **Skip Existing Files**: By default, the system skips files that have already been processed in previous steps. Use the `--force` option to reprocess.
- **Memory Handling**: System implements special handling for PDFs that cause memory errors.
- **Incremental CSV**: Each analysis is saved to CSV immediately, preventing data loss in case of failure.
- **CSV Format**: The CSV report uses semicolon separators (;) and ANSI encoding for better compatibility with Excel.
