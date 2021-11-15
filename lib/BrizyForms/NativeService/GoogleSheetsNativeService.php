<?php

namespace BrizyForms\NativeService;

use Google_Service_Sheets;
use Google_Client;

class GoogleSheetsNativeService
{
    /** @var Google_Client */
    private $client;
    /** @var Google_Service_Sheets */
    private $service;
    /** @var string */
    private $spreadsheetId;

    /**
     * GoogleSheetsNativeService constructor.
     * @param string $spreadsheetId
     */
    public function __construct($spreadsheetId)
    {
        $this->spreadsheetId = $spreadsheetId;
        $this->client = new Google_Client();
        $this->client->setApplicationName('app');
        $this->client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
        $this->client->setAccessType('offline');
        $this->service = new \Google_Service_Sheets();
    }

    public function updateRange($coordinates, $values)
    {
        $body = new \Google_Service_Sheets_ValueRange(
            ['values' => $values,]
        );
        $params = [
            'valueInputOption' => 'RAW',
        ];
        $result = $this->service->spreadsheets_values;
    }

    public function appendRow($range, $values)
    {
        $body = new \Google_Service_Sheets_ValueRange(
            ['values' => $values,]
        );
        $params = [
            'valueInputOption' => 'RAW',
            'alt' => 'json',
            "insertDataOption" => "INSERT_ROWS",
        ];

        return $this->service->spreadsheets_values->append(
            $this->spreadsheetId,
            $range,
            $body,
            $params
        );
    }

    public function checkAuthentication()
    {
        return "all MKit";
    }

    public function createSheet($data)
    {
        try {
            $body = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
                'requests' => array(
                    'addSheet' => array(
                        'properties' => array(
                            'title' => $data['title']
                        )
                    )
                )
            ));
            $result = $this->service->spreadsheets->batchUpdate($this->spreadsheetId, $body);
        } catch (\Exception $exception) {
            $result = $exception;
        }
        return $result;
    }

    public function getSpreadsheetSheets()
    {
        $sheets = [];
        $response = $this->service->spreadsheets->get($this->spreadsheetId);
        foreach ($response->getSheets() as $s) {
            $sheets[] = $s['properties'];
        }

        return $sheets;
    }
}
