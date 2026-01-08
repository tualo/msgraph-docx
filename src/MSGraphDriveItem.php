<?php

namespace Tualo\Office\MSGraphDOCX;

use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\Basic\Route as BasicRoute;
use Tualo\Office\Basic\IRoute;
use Microsoft\Kiota\Abstractions\ApiException;

use Tualo\Office\MSGraph\API;
use Tualo\Office\MSGraph\api\MissedTokenException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

use Microsoft\Graph\Generated\Models;
use Microsoft\Graph\Generated\Models\ODataErrors\ODataError;


use Microsoft\Graph\Generated\Users\Item\Messages\MessagesRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Applications\ApplicationsRequestBuilderGetRequestConfiguration;

class DriveItemType
{
    public const BUSINESS = 1;  // 0001
    public const PRIVATE = 2;   // 0010
    public const TEAMS = 4;     // 0100
}

class MSGraphDriveItem
{
    private Models\DriveItem $driveItem;
    private \Microsoft\Graph\GraphServiceClient $client;

    public function __construct(?\Microsoft\Graph\GraphServiceClient $client = null)
    {
        if (is_null($client)) {
            $client = API::GraphClient();
        }
        $this->client = $client;
    }


    private function createListFromDriveItem(Models\DriveCollectionResponse $driveItems, array $resultValues = []): array
    {
        $resultValue = $resultValues;
        foreach ($driveItems->getValue() as $drive) {
            $resultValue[] = [
                'id' => $drive->getId(),
                'name' => $drive->getName(),
                'type' => $drive->getDriveType(),
                'description' => $drive->getDescription(),
                'weburl' => $drive->getWebUrl()
            ];
        }
        return $resultValue;
    }

    public function getDrive(int $driveItemType = DriveItemType::BUSINESS): array
    {
        $resultValue = [];
        try {
            $graphServiceClient = API::GraphClient();

            if ($driveItemType & DriveItemType::BUSINESS) {
                $drives = $graphServiceClient->drives()->get()->wait();
                $resultValue = $this->createListFromDriveItem($drives, $resultValue);
            }

            if ($driveItemType & DriveItemType::PRIVATE) {
                $drives = $graphServiceClient->me()->drives()->get()->wait();
                $resultValue = $this->createListFromDriveItem($drives, $resultValue);
            }

            if ($driveItemType & DriveItemType::TEAMS) {
                $teams = $graphServiceClient->me()->joinedTeams()->get()->wait();
                foreach ($teams->getValue() as $team) {
                    // Das primäre Drive eines Teams
                    $drive = $graphServiceClient->groups()
                        ->byGroupId($team->getId())
                        ->drive()
                        ->get()
                        ->wait();
                    $resultValue[] = [
                        'team_name' => $team->getDisplayName(),
                        'drive_id' => $drive->getId(),
                        'drive_weburl' => $drive->getWebUrl()
                    ];
                }
            }
        } catch (ODataError $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            throw new \Exception("Error getting DriveItems: [$code] $message", $code);
        } catch (ApiException $e) {
            $code = $e->getResponseStatusCode();
            $message = $e->getMessage();
            throw new \Exception("Error getting DriveItems: [$code] $message", $code);
        }
        return $resultValue;
    }
}
