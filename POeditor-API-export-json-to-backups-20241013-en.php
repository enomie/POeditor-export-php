<?php
// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Your POEditor API key
$apiKey = 'APIKEYXXXXXXXXXXXXXXXXXXXXXXXX'; // Replace with your actual API key

// API URLs
$projectsUrl = 'https://api.poeditor.com/v2/projects/list';
$languagesUrl = 'https://api.poeditor.com/v2/languages/list';
$exportUrl = 'https://api.poeditor.com/v2/projects/export';

// Backup directory
$backupDir = __DIR__ . '/backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true); // Create directory if it doesn't exist
}

// Function to send a POST request
function sendPostRequest($url, $data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    if ($response === false) {
        die('cURL error: ' . curl_error($ch));
    }
    curl_close($ch);
    return json_decode($response, true);
}

// Function to download and save a file with cURL
function downloadFileWithCurl($url, $filePath) {
    $ch = curl_init($url);
    $fp = fopen($filePath, 'w+');

    curl_setopt($ch, CURLOPT_FILE, $fp); // Save the file directly
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Timeout
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Verify SSL certificates

    curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Error downloading the file: ' . curl_error($ch) . "<br>";
    } else {
        echo "File successfully downloaded to $filePath.<br>";
    }

    curl_close($ch);
    fclose($fp);
}

// Function to export and save translations as JSON
function saveTranslationBackup($apiKey, $projectId, $languageCode, $backupDir) {
    global $exportUrl;
    $data = [
        'api_token' => $apiKey,
        'id' => $projectId,
        'language' => $languageCode,
        'type' => 'json'
    ];
    $response = sendPostRequest($exportUrl, $data);
    if (isset($response['result']['url'])) {
        $backupFilePath = $backupDir . "project_{$projectId}_{$languageCode}.json";
        downloadFileWithCurl($response['result']['url'], $backupFilePath);
    } else {
        echo "Error creating backup for project $projectId, language $languageCode.<br>";
    }
}

// Fetch projects
$responseProjects = sendPostRequest($projectsUrl, ['api_token' => $apiKey]);

// Check if the request was successful
if (isset($responseProjects['response']['status']) && $responseProjects['response']['status'] === 'success') {
    // Loop through all projects
    foreach ($responseProjects['result']['projects'] as $project) {
        $projectId = $project['id'];
        $projectName = $project['name'];
        
        echo "<h1>Project: $projectName (ID: $projectId)</h1>";
        
        // Fetch languages for each project
        $responseLanguages = sendPostRequest($languagesUrl, ['api_token' => $apiKey, 'id' => $projectId]);

        // Check if the language request was successful
        if (isset($responseLanguages['response']['status']) && $responseLanguages['response']['status'] === 'success') {
            // Loop through all languages and save a backup for each
            foreach ($responseLanguages['result']['languages'] as $language) {
                $languageCode = $language['code'];
                echo "Saving backup for language: " . $language['name'] . " ($languageCode)...<br>";
                saveTranslationBackup($apiKey, $projectId, $languageCode, $backupDir);
            }
        } else {
            echo "Error fetching languages for project $projectName.<br>";
        }

        echo "<hr>"; // Divider between projects
    }
} else {
    echo "Error fetching projects.<br>";
}

?>
