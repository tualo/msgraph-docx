<?php

namespace Tualo\Office\MSGraphDOCX\Routes\Setup;

use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\Basic\Route as BasicRoute;
use Tualo\Office\MSGraphDOCX\MSGraphDriveItem;
use Tualo\Office\MSGraphDOCX\MSGraphFile;
use Tualo\Office\MSGraphDOCX\DriveItemType;

class Open extends \Tualo\Office\Basic\RouteWrapper
{
    public static function register()
    {
        BasicRoute::add('/msgraph-docx/open/(?P<file_id>.+)', function ($matches) {
            try {
                $db = App::get('session')->getDB();
                $files = $db->direct("select * from doc_binary where document_link = {id}", [
                    'id' => $matches['file_id']
                ]);
                if (count($files) == 0) {
                    throw new \Exception('file not found');
                }
                $binaryData = $files[0]['doc_data'];

                $msgraphDriveItem = new MSGraphDriveItem();
                $drives = $msgraphDriveItem->getDrive(DriveItemType::BUSINESS | DriveItemType::PRIVATE | DriveItemType::TEAMS);
                $msgraphFile = new MSGraphFile();
                $uploadResult = $msgraphFile->upload($drives[0]['id'], $matches['file_id'] . '.docx', $binaryData);
                App::result('uploadResult', $uploadResult);
                $msgraphFile->subscripe($drives[0]['id'], $uploadResult['id']);
                if (isset($uploadResult['webUrl'])) {
                    header('Location: ' . $uploadResult['webUrl']);
                    exit;;
                }
                App::result('success', true);
            } catch (\Exception $e) {
                App::result('msg', $e->getMessage());
            }
            App::contenttype('application/json');
        }, ['get'], true);
    }
}
