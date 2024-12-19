<?php

require __DIR__ . '/vendor/autoload.php';

// Define your Discord webhooks and GitHub repository redirects
$discord_webhooks = [
    "lrp" => "https://discordapp.com/api/webhooks/1260349898524463264/VEWbPC1dIfyTLt-Ews3nty7D6GTt-DW02vk1n-wH2MfmPw-P-FTG0FQvMQ5kWA9XZ4hz",
    "biblebot" => "https://discordapp.com/api/webhooks/1270891533368950885/laXFEfS33pifwzEZw2bJx4xzzHMLZ8GEYBdz9KiTdApPwr9sR04krTA5aZloUTz_jq5f",
    "yumhack" => "https://discord.com/api/webhooks/1279849031509737584/Grnz3I-nk6fDZOsyMABBjo5L42mPaAwwEZdg6SUz451srYib_02g3W2p6N5r3W4lMrfc",
    "phexserver" => "https://discord.com/api/webhooks/1279849031509737584/Grnz3I-nk6fDZOsyMABBjo5L42mPaAwwEZdg6SUz451srYib_02g3W2p6N5r3W4lMrfc",
    "onelaunch" => "https://discord.com/api/webhooks/1279849031509737584/Grnz3I-nk6fDZOsyMABBjo5L42mPaAwwEZdg6SUz451srYib_02g3W2p6N5r3W4lMrfc",
    "phexbot" => "https://discord.com/api/webhooks/1279849031509737584/Grnz3I-nk6fDZOsyMABBjo5L42mPaAwwEZdg6SUz451srYib_02g3W2p6N5r3W4lMrfc",
    "onebot" => "https://discord.com/api/webhooks/1279849031509737584/Grnz3I-nk6fDZOsyMABBjo5L42mPaAwwEZdg6SUz451srYib_02g3W2p6N5r3W4lMrfc",
    "test" => [
        "https://discord.com/api/webhooks/1279849031509737584/Grnz3I-nk6fDZOsyMABBjo5L42mPaAwwEZdg6SUz451srYib_02g3W2p6N5r3W4lMrfc",
        "https://discord.com/api/webhooks/1295381580147195996/TVcHQA_o8jhXRmwXbbmWhbqEoa6He4yXI2o8MJ4aHKKSAolOZEleTxPydBmTFjzCYU_p",
    ],
    "xylosrp" => "https://discord.com/api/webhooks/1319055064232493097/5lWox-RZ7j_lVbtZKXrbNmSUqSoWorviOyy23Ymux_5ym08U42p1CYHDh5jUrrUe2ODZ",
    "xylosweb" => "https://discord.com/api/webhooks/1319055064232493097/5lWox-RZ7j_lVbtZKXrbNmSUqSoWorviOyy23Ymux_5ym08U42p1CYHDh5jUrrUe2ODZ"
];

$github_repos = [
    "wispjs" => "https://github.com/Maineiac/wisp-js",
    "yumhack" => "https://github.com/Graped-OHOL/YumHack",
    "onelaunch" => "https://github.com/Graped-OHOL/OneLaunch"
];

// Load the .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Define your GitHub webhook secret from .env
define('GITHUB_SECRET', $_ENV['GITHUB_SECRET']);

?>