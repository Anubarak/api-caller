<?php
/**
 * API Caller  plugin for Craft CMS 3.x
 *
 * A Plugin to call APIs
 *
 * @link      https://www.secondred.de/
 * @copyright Copyright (c) 2018 Robin Schambach
 */

namespace secondred\apicaller\services;

use craft\elements\Entry;
use craft\helpers\Assets;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use secondred\apicaller\ApiCaller;

use Craft;
use craft\base\Component;

/**
 * ApiCallerService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Robin Schambach
 * @package   ApiCaller
 * @since     1.0.0
 */
class ApiCallerService extends Component
{

    private $_folder;

    // Public Methods
    // =========================================================================
    /**
     * @param array $criteria
     * @return mixed
     */
    public function fetchImages($criteria = [], $settings = null)
    {
        if($settings === null){
            $settings = ApiCaller::$plugin->getSettings();
        }

        $query = Entry::find();
        Craft::configure($query, $criteria);
        foreach($query->all() as $entry){
            $field = $entry->getFieldValue($settings->sourceField);
            $assetId = $this->fetchImage($criteria, $field, $entry->title, $settings);
            if($assetId){
                $entry->setFieldValue($settings->targetField, [$assetId]);
                Craft::$app->getElements()->saveElement($entry);
            }
        }

        return true;
    }

    /**
     * @param $criteria
     * @param $domain
     * @param $fileName
     * @param null $settings
     * @return int|null|string
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
     */
    public function fetchImage($criteria, $domain, $fileName, $settings = null){
        if($settings === null){
            $settings = ApiCaller::$plugin->getSettings();
        }

        $ch = curl_init();
        $targetUrl = 'https://api.ritekit.com/v1/images/logo?domain=' . $domain . '&client_id=' . $settings->clientId;

        // set url
        curl_setopt($ch, CURLOPT_URL, $targetUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if($info['content_type'] === 'image/png'){
            $fileName = str_replace('.', '', $fileName);
            $path = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $fileName . '.png';
            FileHelper::writeToFile($path, $output);
            $assetId = $this->uploadImage($path, $fileName, $settings);

            return $assetId;
        }else{
            $json = Json::decode($output);

            return false;
        }
    }

    /**
     * @param $path
     * @param $fileName
     * @param $settings
     * @return int|null|string
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    public function uploadImage($path, $fileName, $settings){
        $folder = $this->getFolder($settings);

        $asset = new \craft\elements\Asset();
        $asset->tempFilePath = $path;
        $asset->filename = $fileName . '.png';
        $asset->newFolderId = $folder->id;
        $asset->volumeId = $folder->volumeId;
        $asset->avoidFilenameConflicts = true;
        $asset->setScenario(\craft\elements\Asset::SCENARIO_CREATE);

        $result = Craft::$app->getElements()->saveElement($asset);

        // In case of error, let user know about it.
        if(!$result){
            $errors = $asset->getFirstErrors();
            echo("<pre>");
            var_dump($asset->getErrors());
            echo("</pre>");
            //return Craft::t('app', "Failed to save the Asset:\n") . implode(";\n", $errors);
        }

        return $asset->hasErrors() === false? $asset->id : false;
    }

    public function getFolder($settings){
        if($this->_folder === null){
            $this->_folder = Craft::$app->getAssets()->findFolder(['id' => $settings->folderId]);
        }

        return $this->_folder;
    }
}
