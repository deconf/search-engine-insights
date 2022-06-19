<?php
/**
 * @license Apache-2.0
 *
 * Modified by __root__ on 18-June-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

if (class_exists('Deconf_SEIWP_Google_Client', false)) {
    // Prevent error with preloading in PHP 7.4
    // @see https://github.com/googleapis/google-api-php-client/issues/1976
    return;
}

$classMap = [
    'Deconf\SEIWP\Google\\Client' => 'Deconf_SEIWP_Google_Client',
    'Deconf\\SEIWP\\Google\\Service' => 'Deconf_SEIWP_Google_Service',
    'Deconf\\SEIWP\\Google\\AccessToken\\Revoke' => 'Deconf_SEIWP_Google_AccessToken_Revoke',
    'Deconf\\SEIWP\\Google\\AccessToken\\Verify' => 'Deconf_SEIWP_Google_AccessToken_Verify',
    'Deconf\SEIWP\Google\\Model' => 'Deconf_SEIWP_Google_Model',
    'Deconf\\SEIWP\\Google\\Utils\\UriTemplate' => 'Deconf_SEIWP_Google_Utils_UriTemplate',
    'Deconf\\SEIWP\\Google\\AuthHandler\\Guzzle6AuthHandler' => 'Deconf_SEIWP_Google_AuthHandler_Guzzle6AuthHandler',
    'Deconf\\SEIWP\\Google\\AuthHandler\\Guzzle7AuthHandler' => 'Deconf_SEIWP_Google_AuthHandler_Guzzle7AuthHandler',
    'Deconf\\SEIWP\\Google\\AuthHandler\\Guzzle5AuthHandler' => 'Deconf_SEIWP_Google_AuthHandler_Guzzle5AuthHandler',
    'Deconf\\SEIWP\\Google\\AuthHandler\\AuthHandlerFactory' => 'Deconf_SEIWP_Google_AuthHandler_AuthHandlerFactory',
    'Deconf\\SEIWP\\Google\\Http\\Batch' => 'Deconf_SEIWP_Google_Http_Batch',
    'Deconf\\SEIWP\\Google\\Http\\MediaFileUpload' => 'Deconf_SEIWP_Google_Http_MediaFileUpload',
    'Deconf\\SEIWP\\Google\\Http\\REST' => 'Deconf_SEIWP_Google_Http_REST',
    'Deconf\\SEIWP\\Google\\Task\\Retryable' => 'Deconf_SEIWP_Google_Task_Retryable',
    'Deconf\\SEIWP\\Google\\Task\\Exception' => 'Deconf_SEIWP_Google_Task_Exception',
    'Deconf\\SEIWP\\Google\\Task\\Runner' => 'Deconf_SEIWP_Google_Task_Runner',
    'Deconf\SEIWP\Google\\Collection' => 'Deconf_SEIWP_Google_Collection',
    'Deconf\\SEIWP\\Google\\Service\\Exception' => 'Deconf_SEIWP_Google_Service_Exception',
    'Deconf\\SEIWP\\Google\\Service\\Resource' => 'Deconf_SEIWP_Google_Service_Resource',
    'Deconf\SEIWP\Google\\Exception' => 'Deconf_SEIWP_Google_Exception',
];

foreach ($classMap as $class => $alias) {
    class_alias($class, $alias);
}

/**
 * This class needs to be defined explicitly as scripts must be recognized by
 * the autoloader.
 */
class Deconf_SEIWP_Google_Task_Composer extends \Deconf\SEIWP\Google\Task\Composer
{
}

/** @phpstan-ignore-next-line */
if (\false) {
    class Deconf_SEIWP_Google_AccessToken_Revoke extends \Deconf\SEIWP\Google\AccessToken\Revoke
    {
    }
    class Deconf_SEIWP_Google_AccessToken_Verify extends \Deconf\SEIWP\Google\AccessToken\Verify
    {
    }
    class Deconf_SEIWP_Google_AuthHandler_AuthHandlerFactory extends \Deconf\SEIWP\Google\AuthHandler\AuthHandlerFactory
    {
    }
    class Deconf_SEIWP_Google_AuthHandler_Guzzle5AuthHandler extends \Deconf\SEIWP\Google\AuthHandler\Guzzle5AuthHandler
    {
    }
    class Deconf_SEIWP_Google_AuthHandler_Guzzle6AuthHandler extends \Deconf\SEIWP\Google\AuthHandler\Guzzle6AuthHandler
    {
    }
    class Deconf_SEIWP_Google_AuthHandler_Guzzle7AuthHandler extends \Deconf\SEIWP\Google\AuthHandler\Guzzle7AuthHandler
    {
    }
    class Deconf_SEIWP_Google_Client extends \Deconf\SEIWP\Google\Client
    {
    }
    class Deconf_SEIWP_Google_Collection extends \Deconf\SEIWP\Google\Collection
    {
    }
    class Deconf_SEIWP_Google_Exception extends \Deconf\SEIWP\Google\Exception
    {
    }
    class Deconf_SEIWP_Google_Http_Batch extends \Deconf\SEIWP\Google\Http\Batch
    {
    }
    class Deconf_SEIWP_Google_Http_MediaFileUpload extends \Deconf\SEIWP\Google\Http\MediaFileUpload
    {
    }
    class Deconf_SEIWP_Google_Http_REST extends \Deconf\SEIWP\Google\Http\REST
    {
    }
    class Deconf_SEIWP_Google_Model extends \Deconf\SEIWP\Google\Model
    {
    }
    class Deconf_SEIWP_Google_Service extends \Deconf\SEIWP\Google\Service
    {
    }
    class Deconf_SEIWP_Google_Service_Exception extends \Deconf\SEIWP\Google\Service\Exception
    {
    }
    class Deconf_SEIWP_Google_Service_Resource extends \Deconf\SEIWP\Google\Service\Resource
    {
    }
    class Deconf_SEIWP_Google_Task_Exception extends \Deconf\SEIWP\Google\Task\Exception
    {
    }
    interface Deconf_SEIWP_Google_Task_Retryable extends \Deconf\SEIWP\Google\Task\Retryable
    {
    }
    class Deconf_SEIWP_Google_Task_Runner extends \Deconf\SEIWP\Google\Task\Runner
    {
    }
    class Deconf_SEIWP_Google_Utils_UriTemplate extends \Deconf\SEIWP\Google\Utils\UriTemplate
    {
    }
}
