# Curricula 📄🔍

A resume analysis pipeline using PHP and AI for efficient candidate screening based on customizable criteria.

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.0-8892BF.svg)](https://www.php.net/)

## 🔍 Overview

Curricula is an open source solution for processing, standardizing, and analyzing resumes using AWS Bedrock and Claude. The project enables automated screening of resumes in PDF and DOCX formats, scoring candidates based on customizable criteria for specific job positions.

### 🌟 Key Features

- **Complete Processing**: Text extraction from PDFs and DOCXs, including OCR fallback
- **Intelligent Standardization**: Reorganization of content into consistent Markdown format
- **AI Analysis**: Candidate evaluation using customizable weighted criteria
- **Detailed Reports**: Generation of individual reports and consolidated CSV
- **Modular and Flexible**: Use the complete pipeline or just specific steps
- **Robust**: Error handling and detailed logs at each step

## ⚙️ Requirements

- PHP 8.0+
- Composer
- AWS account with Bedrock access
- Dependencies:
  - AWS SDK for PHP
  - PDF and DOCX processing libraries
  - Tesseract OCR (optional, for problematic PDFs)

## 🚀 Installation

1. Clone the repository:
```bash
git clone https://github.com/phrbarbosa/curricula.git
cd curricula
```

2. Install dependencies:
```bash
composer install
```

3. Configure your AWS credentials:
```bash
cp .env.example .env
```
Edit the `.env` file with your AWS credentials and model settings.

4. Create the necessary folders (or use the `php bin/console cv:setup` command if available):
```bash
mkdir -p data/{input,extracted,processed,analysis,report,log/{extract,process,analyze},temp}
```

## 📋 Configuration

1. Place PDF/DOCX resumes in the `data/input/` folder

2. Configure job requirements in `config/job-requirements.json`:
   - Job description and position
   - Evaluation criteria with weights
   - Output language
   - Working directories

Configuration example:
```json
{
  "job_description": "Complete job description...",
  "position": "Full-Stack Developer",
  "output_language": "en-US",
  "evaluation_criteria": {
    "technical_skills": {
      "weight": 0.35,
      "description": "Evaluate technical knowledge..."
    },
    "experience": {
      "weight": 0.25,
      "description": "Consider previous experience..."
    },
    ...
  }
}
```

## 🔧 Usage

### Complete Pipeline

```bash
php bin/console cv:run-all
```

Options:
- `--force`: Reprocess already processed files
- `--job-requirements=path/file.json`: Use custom requirements file

### Individual Commands

#### 1. Text Extraction

```bash
php bin/console cv:extract [--force]
```

#### 2. Resume Standardization

```bash
php bin/console cv:process [file.txt] [--force]
```

#### 3. Resume Analysis

```bash
php bin/console cv:analyze [standardized_file.txt] [--force]
```

### Help

```bash
php bin/console help [command]
```

## 📊 Results

- **Extracted Texts**: Saved in `data/extracted/`
- **Standardized Resumes**: Saved in `data/processed/` with suffix `_standardized.txt`
- **Individual Analyses**: Saved in `data/analysis/` with suffix `_analysis.txt`
- **CSV Report**: Generated in `data/report/consolidated_analysis_DATE.csv`
- **Logs**: Errors and warnings are recorded in `data/log/`

## 🧩 Project Structure

```
├── bin/                # Executable scripts
├── config/             # Configuration files
├── data/               # Working directories
│   ├── input/          # Original resumes (PDF/DOCX)
│   ├── extracted/      # Extracted texts
│   ├── processed/      # Standardized resumes
│   ├── analysis/       # Individual analyses
│   ├── report/         # Consolidated reports
│   ├── log/            # Error logs
│   └── temp/           # Temporary files
├── src/                # Source code
│   ├── Command/        # Symfony Console commands
│   └── Service/        # Services and business logic
└── vendor/             # Dependencies (via Composer)
```

## 🛠️ Customization

### Directories

The directories used by the system can be configured in the `directories` section of the `config/job-requirements.json` file.

### AWS Configuration

AWS credentials and settings are defined in the `.env` file:

```
AWS_ACCESS_KEY=your_access_key
AWS_SECRET_KEY=your_secret_key
AWS_REGION=us-east-1
AWS_MODEL_ID=anthropic.claude-3-5-sonnet-20240620-v1:0
```

## 🤝 Contributing

Contributions are welcome! Feel free to submit pull requests, create issues, or suggest improvements.

1. Fork the project
2. Create your feature branch (`git checkout -b feature/MyFeature`)
3. Commit your changes (`git commit -am 'Add new feature'`)
4. Push to the branch (`git push origin feature/MyFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the [MIT License](LICENSE).

## 👤 Author

**Pedro Rosa**

- GitHub: [@phrbarbosa](https://github.com/phrbarbosa)

## 📚 Use Cases

- **Recruitment**: Initial screening of candidates for positions with high volume of resumes
- **Education**: Analysis and classification of academic profiles
- **Research**: Structured extraction of information from professional documents

## 🙏 Acknowledgments

Special thanks to everyone who contributed to this project through code, documentation, or feedback.
