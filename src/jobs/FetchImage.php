<?php
/**
 * API Caller  plugin for Craft CMS 3.x
 *
 * A Plugin to call APIs
 *
 * @link      https://www.secondred.de/
 * @copyright Copyright (c) 2018 Robin Schambach
 */

namespace secondred\apicaller\jobs;

use craft\db\QueryAbortedException;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\errors\ElementNotFoundException;
use craft\helpers\App;
use secondred\apicaller\ApiCaller;

use Craft;
use craft\queue\BaseJob;
use yii\base\ErrorException;
use yii\base\Exception;

/**
 * FetchImage job
 *
 * Jobs are run in separate process via a Queue of pending jobs. This allows
 * you to spin lengthy processing off into a separate PHP process that does not
 * block the main process.
 *
 * You can use it like this:
 *
 * use secondred\apicaller\jobs\FetchImage as FetchImageJob;
 *
 * $queue = Craft::$app->getQueue();
 * $jobId = $queue->push(new FetchImageJob([
 *     'description' => Craft::t('api-caller', 'This overrides the default description'),
 *     'someAttribute' => 'someValue',
 * ]));
 *
 * The key/value pairs that you pass in to the job will set the public properties
 * for that object. Thus whatever you set 'someAttribute' to will cause the
 * public property $someAttribute to be set in the job.
 *
 * Passing in 'description' is optional, and only if you want to override the default
 * description.
 *
 * More info: https://github.com/yiisoft/yii2-queue
 *
 * @author    Robin Schambach
 * @package   ApiCaller
 * @since     1.0.0
 */
class FetchImage extends BaseJob
{
    // Public Properties
    // =========================================================================

    /**
     * Some attribute
     *
     * @var array
     */
    public $criteria = [];
    public $settings;

    // Public Methods
    // =========================================================================

    /**
     * When the Queue is ready to run your job, it will call this method.
     * You don't need any steps or any other special logic handling, just do the
     * jobs that needs to be done here.
     *
     * More info: https://github.com/yiisoft/yii2-queue
     */
    public function execute($queue)
    {
        $settings = $this->settings;
        $criteria = $this->criteria;
        // Now find the affected element IDs
        /** @var EntryQuery $query */
        $query = Entry::find();
        if (!empty($this->criteria)) {
            Craft::configure($query, $this->criteria);
        }
        $query
            ->offset(null)
            ->limit(null)
            ->orderBy(null);

        $totalElements = $query->count();
        $currentElement = 0;

        try {
            foreach ($query->each() as $element) {
                $this->setProgress($queue, $currentElement++ / $totalElements);
                $field = $element->getFieldValue($settings->sourceField);
                $assetId = ApiCaller::$plugin->getService()->fetchImage($field, $element, $settings);
                if($assetId){
                    $element->setFieldValue($settings->targetField, [$assetId]);
                    if (!Craft::$app->getElements()->saveElement($element)) {
                        Craft::error('[API CALLER] Couldn’t save element '.$element->id);
                        //throw new Exception('Couldn’t save element '.$element->id.' ('.get_class($element).') due to validation errors.');
                    }
                }

            }
        } catch (QueryAbortedException $e) {
            // Fail silently
        }catch(ElementNotFoundException $e){
        }catch(ErrorException $e){
        }catch(Exception $e){
        }catch(\Throwable $e){
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns a default description for [[getDescription()]], if [[description]] isn’t set.
     *
     * @return string The default task description
     */
    protected function defaultDescription(): string
    {
        return Craft::t('api-caller', 'FetchImage');
    }
}
