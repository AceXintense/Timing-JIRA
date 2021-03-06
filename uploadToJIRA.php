<?php

use Core\Authentication;
use Core\Console;
use Core\CSVParser;
use Core\Files;
use Core\JIRA;
use Models\Worklog;
include 'vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

/*
 * Initial setup JIRA Login, Data file loading.
 */
Authentication::setUsernameAndPassword(getenv('JIRA_USERNAME'), getenv('JIRA_PASSWORD'), getenv('JIRA_PASSWORD_BASE64'));
JIRA::setURL(getenv('JIRA_URL'));

/*
 * Loops through all the files in the directory specified in the config.
 */
foreach (Files::getInstance()->getFilesInDirectory(getenv('DATA_DIRECTORY')) as $file) {

    CSVParser::getInstance()->load(getenv('DATA_DIRECTORY') . $file);

    try {
        //Loop through each row on the CSV and upload the Data.
        foreach (CSVParser::getInstance()->format()->getFormattedRows() as $formattedRow) {
            JIRA::addWorklog(
                Worklog::getIssueKey($formattedRow['Task Title'], Worklog::DASH_SEPARATED_ISSUE_NUMBER),
                new Worklog(
                    Worklog::getIssueKey($formattedRow['Task Title'], Worklog::DASH_SEPARATED_ISSUE_NUMBER),
                    JIRA::formatDate($formattedRow['Start Date']),
                    (float)$formattedRow['Duration'],
                    $formattedRow['Notes']
                )
            );
        }
    } catch (Exception $exception) {
        Console::log($exception->getMessage());
    } catch (\GuzzleHttp\Exception\GuzzleException $exception) {
        Console::log($exception->getMessage());
    }

}
