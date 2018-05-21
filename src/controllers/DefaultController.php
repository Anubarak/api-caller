<?php
/**
 * API Caller  plugin for Craft CMS 3.x
 *
 * A Plugin to call APIs
 *
 * @link      https://www.secondred.de/
 * @copyright Copyright (c) 2018 Robin Schambach
 */

namespace secondred\apicaller\controllers;

use secondred\apicaller\ApiCaller;

use Craft;
use craft\web\Controller;
use secondred\apicaller\jobs\FetchImage;

/**
 * Default Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Robin Schambach
 * @package   ApiCaller
 * @since     1.0.0
 */
class DefaultController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['index'];

    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to our plugin's index action URL,
     * e.g.: actions/api-caller/default
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $settings = Craft::$app->getRequest()->getBodyParam('settings', []);

        $criteria = [];
        $criteria[$settings['targetField']] = ':empty:';
        $criteria[$settings['sourceField']] = ':notempty:';
        $criteria['sectionId'] = $settings['sectionId'];

        // start the job
        Craft::$app->getQueue()->push(new FetchImage([
            'criteria'  => $criteria,
            'settings'  => (object)$settings
        ]));

        //ApiCaller::$plugin->getService()->fetchImages($criteria, (object)$settings);
        return $this->asJson([
            'success'   => true,
            'message'   => 'Started the queue'
        ]);
    }
}
