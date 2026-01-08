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


class MSGraphFile
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



    public function upload(string $useDriveID, string $fileName, string $binaryData): array
    {
        $resultValue = [];
        try {
            // Stream erstellen für den Upload
            $stream = \GuzzleHttp\Psr7\Utils::streamFor($binaryData);

            // Datei direkt hochladen (für Dateien < 250MB)
            $result = $this->client->drives()
                ->byDriveId($useDriveID)
                ->items()
                ->byDriveItemId('root:/' . $fileName . ':')
                ->content()
                ->put($stream)
                ->wait();

            App::result('result', $result);

            $itemId = $result->getId();
            $webUrl = $result->getWebUrl();  // URL zum Öffnen im Browser
            $name = $result->getName();
            $size = $result->getSize();
            $eTag = $result->getETag();
            $createdDateTime = $result->getCreatedDateTime();
            $lastModifiedDateTime = $result->getLastModifiedDateTime();
            $resultValue = [
                'id' => $itemId,
                'webUrl' => $webUrl,
                'name' => $name,
                'size' => $size,
                'eTag' => $eTag,
                'createdDateTime' => $createdDateTime,
                'lastModifiedDateTime' => $lastModifiedDateTime
            ];
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

    public function subscripe(string $resourceId): array
    {
        $resultValue = [];
        try {
            $baseWebHookURL = App::configuration('msgraph', 'base_webhook_url', '');
            if ($baseWebHookURL == '') {
                throw new \Exception('no base_webhook_url configured in msgraph configuration');
            }
            $session = App::get('session');
            $token = $session->registerOAuth(
                // $params = ['cmp' => 'cmp_ds'],
                $force = true,
                $anyclient = false,
                $path = '/msgraph-docx/webhook',
                $name = 'MSGraph Webhook',
                $device = 'Server',
            );
            $session->oauthValidDays($token, 3);
            $expirationDateTime = new \DateTime();
            $expirationDateTime->modify('+4230 minutes'); // Maximale Laufzeit für Web
            $subscription = new Models\Subscription();
            $subscription->setChangeType('updated,deleted');
            $urlParts = [
                $baseWebHookURL,
                '~',
                $token,
                'msgraph-docx',
                'webhook'
            ];
            $subscription->setNotificationUrl(implode('/', $urlParts));
            $subscription->setResource($resourceId);
            $subscription->setExpirationDateTime($expirationDateTime);
            $subscription->setClientState('secretClientValue');
            $subscription->setLatestSupportedTlsVersion('v1_2');



            $createdSubscription = $this->client->subscriptions()
                ->post($subscription)
                ->wait();

            $resultValue = [
                'id' => $createdSubscription->getId(),
                'resource' => $createdSubscription->getResource(),
                'expirationDateTime' => $createdSubscription->getExpirationDateTime()
            ];
        } catch (ODataError $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            throw new \Exception("Error creating subscription: [$code] $message", $code);
        } catch (ApiException $e) {
            $code = $e->getResponseStatusCode();
            $message = $e->getMessage();
            throw new \Exception("Error creating subscription: [$code] $message", $code);
        }
        return $resultValue;
    }
}
