<?php
/**
* 2016-2017 EEWEE
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <prestashop@eewee.fr>
*  @copyright 2016-2017 EEWEE
*  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/**
 * Class EeweeslackLogModel
 */
class EeweeslackLogModel extends ObjectModel
{
    /** @var string Name */
    public $id;
    public $id_eewee_slack_log;
    public $date_add;
    public $message;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table'         => 'eewee_slack_log',
        'primary'       => 'id_eewee_slack_log',
        'multilang'     => false,
        'multilang_shop'=> false,
        'fields' => array(
            'date_add'  => array('type' => self::TYPE_DATE,   'validate' => 'isDate'),
            'message'   => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255),
        ),
    );

    /**
     * Adds current eewee_slack_log as a new Object to the database
     *
     * @param bool $autoDate    Automatically set `date_upd` and `date_add` columns
     * @param bool $nullValues Whether we want to use NULL values instead of empty quotes values
     *
     * @return bool Indicates whether the eewee_slack_log has been successfully added
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = false)
    {
        return parent::add($autoDate, true);
    }
}
