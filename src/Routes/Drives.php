<?php

namespace Tualo\Office\MSGraphDOCX\Routes\Setup;

use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\Basic\Route as BasicRoute;
use Tualo\Office\MSGraphDOCX\MSGraphDriveItem;
use Tualo\Office\MSGraphDOCX\DriveItemType;

class Drives extends \Tualo\Office\Basic\RouteWrapper
{
    public static function register()
    {
        BasicRoute::add('/msgraph-docx/drives', function ($matches) {
            try {
                // $db = App::get('session')->getDB();
                $msgraphDriveItem = new MSGraphDriveItem();
                $drives = $msgraphDriveItem->getDrive(DriveItemType::BUSINESS | DriveItemType::PRIVATE | DriveItemType::TEAMS);
                App::result('drives', $drives);
                App::result('success', true);
            } catch (\Exception $e) {
                App::result('msg', $e->getMessage());
            }
            App::contenttype('application/json');
        }, ['get'], true);
    }
}
