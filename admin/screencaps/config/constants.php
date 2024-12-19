<?php
/*
 * Leyscreencap Web (https://demo.maddela.org/leyscreencap/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/leyscreencap-web
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2021 kanalumaddela
 * @license   MIT
 */

define('DEBUG', false); // displays php errors and allows use of {{ dump() }} in templates, default: false

define('ENABLE_CACHE', true); // cache data retrieved for faster load times, default: true

define('ENABLE_LOGGING', true); // enable logging, stored in data/logs

define('ENABLE_FRIENDLY_URLS', true); // enable seo friendly urls, default: false

define('SESSION_LENGTH', 604800); // length to stay logged in, this probably won't work properly

define('ITEMS_PER_PAGE', 16); // # of users/screenshots/etc per page

define('APP_TIMEZONE', 'America/New_York'); // timezone to use for date(), see: https://www.php.net/manual/en/timezones.php

define('IMAGE_DATE_FORMAT', 'm/d/Y @ h:i:s A'); // timestamp format for images

define('LOG_DATE_FORMAT', 'm-d-Y H:i:s'); // timestamp format for a single log

define('SCRIPT_OWNER', '76561198005042785'); // define owner of script ( ͡° ͜ʖ ͡°)
