<?php

const PARSER_NEW_API_ENABLE = true;
const GOLANG_LOADER_LIVE_ENABLE = true;
const GOLANG_LOADER_LINE_ENABLE = true;
const GOLANG_LOADER_LIVE_PUB_SUB_EVENT_IDS_KEY = 'PRS:FB:LIVE:LOADER_GO:EVENT_IDS';
const GOLANG_LOADER_LIVE_PUB_SUB_PROXIES_KEY = 'PRS:FB:LIVE:LOADER_GO:PROXIES';
const GOLANG_LOADER_LIVE_FAILED_PROXIES_KEY = 'PRS:FB:LIVE:LOADER_GO:FAILED_PROXIES';

const GOLANG_LOADER_LINE_PUB_SUB_EVENT_IDS_KEY = 'PRS:FB:LINE:LOADER_GO:EVENT_IDS';
const GOLANG_LOADER_LINE_PUB_SUB_PROXIES_KEY = 'PRS:FB:LINE:LOADER_GO:PROXIES';
const GOLANG_LOADER_LINE_FAILED_PROXIES_KEY = 'PRS:FB:LINE:LOADER_GO:FAILED_PROXIES';

define('PARSER_LIVE_ID', 6);
define('PARSER_LINE_ID', 5);

define('LIVE_CONTENT_NAME', 'live');
define('LINE_CONTENT_NAME', 'line');

define('PROXY_LINE_TYPE', 0);
define('PROXY_LIVE_TYPE', 1);

const CACHE_KEY_RAW_LIVE_EVENTS = 'PRS:FB:LIVE';
const CACHE_KEY_RAW_LINE_EVENTS = 'PRS:FB:LINE';

define('LIVE_DATA_TTL', 90);
define('LIVE_EVENT_TTL', 7);

define('LINE_DATA_TTL', 300);

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '-1');
ini_set('pcre.backtrack_limit', 1000000000000);

define('CURRENT_DIR', dirname(__DIR__));
define('SITE_DIR', CURRENT_DIR . '/site-main/backend');

@require_once SITE_DIR . '/config/config.external.php';

/**
 * @var \Redis $Redis
 */
$Redis = getRedis();
