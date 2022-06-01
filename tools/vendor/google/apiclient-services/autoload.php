<?php

// For older (pre-2.7.2) verions of google/apiclient
if (
    file_exists(__DIR__ . '/../apiclient/src/Google/Client.php')
    && !class_exists('Deconf_SEIWP_Google_Client', false)
) {
    require_once(__DIR__ . '/../apiclient/src/Google/Client.php');
    if (
        defined('Deconf_SEIWP_Google_Client::LIBVER')
        && version_compare(Deconf_SEIWP_Google_Client::LIBVER, '2.7.2', '<=')
    ) {
        $servicesClassMap = [
            'Deconf\SEIWP\Google\\Client' => 'Deconf_SEIWP_Google_Client',
            'Deconf\\SEIWP\\Google\\Service' => 'Deconf_SEIWP_Google_Service',
            'Deconf\\SEIWP\\Google\\Service\\Resource' => 'Deconf_SEIWP_Google_Service_Resource',
            'Deconf\SEIWP\Google\\Model' => 'Deconf_SEIWP_Google_Model',
            'Deconf\SEIWP\Google\\Collection' => 'Deconf_SEIWP_Google_Collection',
        ];
        foreach ($servicesClassMap as $alias => $class) {
            class_alias($class, $alias);
        }
    }
}
spl_autoload_register(function ($class) {
    if (0 === strpos($class, 'Google_Service_')) {
        // Autoload the new class, which will also create an alias for the
        // old class by changing underscores to namespaces:
        //     Google_Service_Speech_Resource_Operations
        //      => Deconf\SEIWP\Google\Service\Speech\Resource\Operations
        $classExists = class_exists($newClass = str_replace('_', '\\', $class));
        if ($classExists) {
            return true;
        }
    }
}, true, true);
