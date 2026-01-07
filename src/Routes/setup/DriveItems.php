<?php

namespace Tualo\Office\MSGraphDOCX\Routes\Setup;

use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\Basic\Route as BasicRoute;
use Tualo\Office\Basic\IRoute;
use Microsoft\Kiota\Abstractions\ApiException;

use Tualo\Office\MSGraph\API;
use Tualo\Office\MSGraph\api\MissedTokenException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

use Microsoft\Graph\Generated\Models;


use Microsoft\Graph\Generated\Users\Item\Messages\MessagesRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Applications\ApplicationsRequestBuilderGetRequestConfiguration;


class DriveItems extends \Tualo\Office\Basic\RouteWrapper
{
    public static function register()
    {
        BasicRoute::add('/msgraph-docx/drives', function ($matches) {
            try {
                $graphServiceClient = API::GraphClient();
                $list = [];
                try {

                    $drives = $graphServiceClient->drives()->get()->wait();
                    if ($drives && $drives->getValue()) {
                        foreach ($drives->getValue() as $drive) {

                            $list[] = [
                                'id' => $drive->getId(),
                                'name' => $drive->getName(),
                                'type' => $drive->getDriveType(),
                                'description' => $drive->getDescription(),
                                'weburl' => $drive->getWebUrl()
                            ];
                        }
                    }
                    App::result('drives', $list);
                } catch (ApiException $ex) {
                    App::result('drives', []);
                    App::result('drives_error', [
                        'code' => $ex->getResponseStatusCode(),
                        'message' => $ex->getError()->getMessage()
                    ]);
                }

                if (count($list) == 0) throw new \Exception('no drives found');

                App::result('success', true);
            } catch (\Exception $e) {
                App::result('error', $e->getMessage());
            }
            App::contenttype('application/json');
        }, ['get'], true);
    }
}
