# POEditor Translation Backup Script

This PHP script uses the POEditor API to back up translations from your projects in JSON format. Each language for each project is exported and saved locally in a specified backup directory.

## Prerequisites

- PHP 7.4 or higher (compatible with PHP 8.3+)
- cURL extension enabled in PHP
- A POEditor API key

The script will retrieve all your POEditor projects and their languages, then export each translation as JSON files, saving them in the /backups/ directory within the project folder.
