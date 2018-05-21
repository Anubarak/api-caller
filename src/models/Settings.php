<?php
/**
 * API Caller  plugin for Craft CMS 3.x
 *
 * A Plugin to call APIs
 *
 * @link      https://www.secondred.de/
 * @copyright Copyright (c) 2018 Robin Schambach
 */

namespace secondred\apicaller\models;

use secondred\apicaller\ApiCaller;

use Craft;
use craft\base\Model;

/**
 * ApiCaller Settings Model
 *
 * This is a model used to define the plugin's settings.
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Robin Schambach
 * @package   ApiCaller
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var int
     */
    public $sectionId;
    /**
     * @var int
     */
    public $folderId;
    /**
     * @var mixed
     */
    public $clientId;
    /**
     * @var string $sourceField handle of the sourceField
     */
    public $sourceField;
    /**
     * @var string $targetField handle of the targetField
     */
    public $targetField;

    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * @return array
     */
    public function rules()
    {
        return [
            ['someAttribute', 'string'],
            ['someAttribute', 'default', 'value' => 'Some Default'],
        ];
    }
}
