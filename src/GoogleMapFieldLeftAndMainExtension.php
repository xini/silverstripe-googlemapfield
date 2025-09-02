<?php

namespace BetterBrief;

use SilverStripe\Core\Environment;
use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;

class GoogleMapFieldLeftAndMainExtension extends Extension
{
    public function onAfterInit()
    {
        $gmapsParams = [
            'callback' => 'googlemapfieldInit',
        ];
        $key = Environment::getEnv('APP_GOOGLE_MAPS_KEY') ?: GoogleMapField::config()->get('api_key');
        if ($key) {
            $gmapsParams['key'] = $key;
        }

        Requirements::css('innoweb/silverstripe-googlemapfield:client/css/GoogleMapField.css');
        Requirements::javascript(
            'innoweb/silverstripe-googlemapfield:client/js/GoogleMapField.js',
            ['defer' => true]
        );
        Requirements::javascript(
            'https://maps.googleapis.com/maps/api/js?' . http_build_query($gmapsParams),
            ['defer' => true]
        );
    }
}
