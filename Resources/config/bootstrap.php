<?php

namespace Eckinox\Resources\config;

class bootstrap {
    public static function changeCronJobEnvironment() {
        # Make every cron job request use the production environment
        # This allows us to avoid issues with logs and caches ramping up quickly and bloating the server's disk
        if (substr($_SERVER['SCRIPT_URL'] ?? '', 0, 5) == '/cron') {
            $_SERVER['APP_ENV'] = 'prod';
            $_SERVER['APP_DEBUG'] = 'prod';
        }

    }
}

bootstrap::changeCronJobEnvironment();
