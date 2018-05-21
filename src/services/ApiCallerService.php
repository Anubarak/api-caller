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

use craft\base\Element;
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
     * @param null $settings
     * @return mixed
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
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
            $assetId = $this->fetchImage($field, $entry, $settings);
            if($assetId){
                $entry->setFieldValue($settings->targetField, [$assetId]);
                Craft::$app->getElements()->saveElement($entry);
            }
        }

        return true;
    }

    /**
     * @param $domain
     * @param $company Element
     * @param null $settings
     * @return int|null|string
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
     */
    public function fetchImage($domain, $company, $settings = null){
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
            $fileName =  $company->id;
            $path = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $fileName . '.png';
            FileHelper::writeToFile($path, $output);
            $assetId = $this->uploadImage($path, $company, $settings);

            return $assetId;
        }else{
            $json = Json::decode($output);

            return false;
        }
    }

    /**
     * @param $path
     * @param $company Element
     * @param $settings
     * @return int|null|string
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    public function uploadImage($path, $company, $settings){
        $folder = $this->getFolder($settings);

        $asset = new \craft\elements\Asset();
        $asset->tempFilePath = $path;
        $asset->filename = $company->id . '.png';
        $asset->title = $company->title;
        $asset->newFolderId = $folder->id;
        $asset->volumeId = $folder->volumeId;
        $asset->avoidFilenameConflicts = true;
        $asset->setScenario(\craft\elements\Asset::SCENARIO_CREATE);

        $result = Craft::$app->getElements()->saveElement($asset);

        return $asset->hasErrors() === false && $result? $asset->id : false;
    }

    /**
     * @param $settings
     * @return \craft\models\VolumeFolder|null
     */
    public function getFolder($settings){
        if($this->_folder === null){
            $this->_folder = Craft::$app->getAssets()->findFolder(['id' => $settings->folderId]);
        }

        return $this->_folder;
    }
}
