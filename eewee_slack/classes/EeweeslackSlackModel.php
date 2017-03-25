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

class EeweeslackSlackModel
{
    /**
     * Send slack message
     *
     * @param $message slack message
     * @param string $channel slack channel
     * @return mixed
     */
    public function send($message, $username="PrestaShop", $channel="general")
    {
        // INIT
        $channel = str_replace("#", "", $channel);

        // DATA
        $data = "payload=" . json_encode(array(
                "channel"       => "#".$channel,
                "username"      => $username,
                "text"          => $message,
                "icon_url"      => _PS_BASE_URL_.__PS_BASE_URI__.basename(_PS_MODULE_DIR_)."/eewee_slack/views/img/prestashop.jpg",
                //"icon_emoji"  => ':ghost:'
            ));

        // SEND
        $webhookUrl = Configuration::get("EEWEE_SLACK_WEBHOOK_URL");
        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        // LOG
        $m_log              = new EeweeslackLogModel();
        $m_log->date_add    = date("Y-m-d H:i:s");
        $m_log->message     = $result;
        $m_log->add();

        return $result;




        // ERROR (https://api.slack.com/incoming-webhooks)

        // invalid_payload :
        // typically indicates that received request is malformed — perhaps the JSON is structured incorrectly, or the message text is not properly escaped. The request should not be retried without correction.

        // user_not_found and channel_not_found :
        // indicate that the user or channel being addressed either do not exist or are invalid. The request should not be retried without modification or until the indicated user or channel is set up.

        // channel_is_archived :
        // indicates the specified channel has been archived and is no longer accepting new messages.

        // action_prohibited :
        // usually means that a team admin has placed some kind of restriction on this avenue of posting messages and that, at least for now, the request should not be attempted again.

        // posting_to_general_channel_denied :
        // is thrown when an incoming webhook attempts to post to the "#general" channel for a team where posting to that channel is 1) restricted and 2) the creator of the same incoming webhook is not authorized to post there. You'll receive this error with a HTTP 403.

        // too_many_attachments :
        // is thrown when an incoming webhook attempts to post a message with greater than 100 attachments. A message can have a maximum of 100 attachments associated with it.
    }

}
