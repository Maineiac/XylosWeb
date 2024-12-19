<?php
// index.php

require 'config.php';

// Log function for debugging
function logMessage($message) {
    $logFile = __DIR__ . "/logs/debug_log.txt";
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - $message\n", FILE_APPEND);
}

// Function to send data to Discord webhooks
function sendToDiscordWebhooks($webhooks, $json_data) {
    if (!is_array($webhooks)) {
        $webhooks = [$webhooks];
    }
    
    foreach ($webhooks as $webhook) {
        $ch = curl_init($webhook);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($result === false || $http_code !== 204) {
            $error = curl_error($ch);
            logMessage("Curl error for webhook $webhook: $error");
        }
        curl_close($ch);
    }
}

// Redirect for specific repo
$uri = trim($_SERVER['REQUEST_URI'], '/');
if (isset($github_repos[$uri])) {
    header('Location: ' . $github_repos[$uri]);
    exit;
}

// Handle GitHub webhook
$uriParts = explode('/', $uri);
logMessage("Received request URI: " . print_r($uriParts, true));
if (count($uriParts) === 2 && $uriParts[0] === 'api') {
    $repoName = $uriParts[1];
    logMessage("Processing repository: $repoName");

    // Validate the secret
    $secret = GITHUB_SECRET;
    $hubSignature = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';

    if (empty($hubSignature)) {
        logMessage('Missing X-Hub-Signature header');
        http_response_code(400);
        exit('Missing X-Hub-Signature header');
    }

    list($algo, $hash) = explode('=', $hubSignature, 2);
    $rawPostData = file_get_contents('php://input');
    logMessage("Received raw payload: " . $rawPostData);

    // Validate the payload with the secret
    $payloadHash = hash_hmac($algo, $rawPostData, $secret);
    logMessage("Computed payload hash: $payloadHash");
    logMessage("Received payload hash: $hash");

    if ($hash !== $payloadHash) {
        logMessage('Invalid secret');
        http_response_code(403);
        exit('Invalid secret');
    }

    // Decode URL-encoded payload
    parse_str($rawPostData, $decodedPayload);
    $jsonPayload = $decodedPayload['payload'] ?? '';
    logMessage("Decoded JSON payload: " . $jsonPayload);

    // Decode the JSON payload
    $data = json_decode($jsonPayload, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        logMessage('Invalid JSON payload: ' . json_last_error_msg());
        http_response_code(400);
        exit('Invalid JSON payload');
    }

    // Log the payload
    $timestamp = time();
    $logFile = __DIR__ . "/logs/log_" . date('Ymd_His', $timestamp) . ".txt";
    file_put_contents($logFile, print_r($data, true));
    logMessage("Payload logged to $logFile");

    // Prepare common data
    $repo = $data['repository']['name'];
    $sender = $data['sender']['login'];
    $sender_av = $data['sender']['avatar_url'];
    $logo = $data['repository']['owner']['avatar_url'];

    // Handle different event types
    $json_data = null;

    if (isset($data['issue'])) {
        $issue = $data['issue'];
        $action = $data['action'];
        
        $description = "New issue created: {$issue['title']}";
        if ($action !== 'opened') {
            $description = "Issue {$action}: {$issue['title']}";
        }
        
        $json_data = json_encode([
            "embeds" => [
                [
                    "type" => "rich",
                    "description" => $description,
                    "url" => $issue['html_url'],
                    "timestamp" => date('c', $timestamp),
                    "color" => 15844367, // A distinct color for issues
                    "footer" => [
                        "text" => $repo,
                        "icon_url" => $logo
                    ],
                    "author" => [
                        "icon_url" => $sender_av,
                        "name" => $sender,
                    ],
                ]
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } elseif (isset($data['release']) && $data['action'] === 'published') {
        $release = $data['release'];
        
        // Log the entire release body for debugging
        logMessage("Release body: " . $release['body']);
        
        // Extract the first image from the description
        $description = $release['body'];
        $image_url = null;
        if (preg_match('/!\[(?:.*?)\]\((.*?)\)/', $description, $match)) {
            $image_url = $match[1];
            logMessage("Extracted image URL: " . $image_url);
            // Remove the first image link from the description
            $description = preg_replace('/!\[(?:.*?)\]\((?:.*?)\)/', '', $description, 1);
        } else {
            logMessage("No image found in release description");
        }
        
        // Trim the description
        $description = trim($description);
        $description = substr($description, 0, 200) . (strlen($description) > 200 ? '...' : '');
        
        $embed = [
            "type" => "rich",
            "title" => "New Release: " . $release['name'],
            "description" => $description,
            "url" => $release['html_url'],
            "timestamp" => date('c', $timestamp),
            "color" => 3066993, // A distinct color for releases
            "footer" => [
                "text" => $repo,
                "icon_url" => $logo
            ],
            "author" => [
                "icon_url" => $sender_av,
                "name" => $sender,
            ]
        ];
    
        // Add image to the embed if available
        if ($image_url) {
            $embed["image"] = [
                "url" => $image_url
            ];
            logMessage("Added image to embed: " . $image_url);
        } else {
            logMessage("No image URL available to add to embed");
        }
    
        $json_data = json_encode([
            "embeds" => [$embed]
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        logMessage("Prepared JSON data for release: " . $json_data);
    } elseif (isset($data['commits']) && !empty($data['commits'])) {
        // Handle commits (pushes), but only if there are actually commits
        $commits = "";
        foreach ($data['commits'] as $commit) {
            $commits .= $commit['message'] . "\n";
        }

        if (!empty($commits)) {
            $json_data = json_encode([
                "embeds" => [
                    [
                        "type" => "rich",
                        "description" => $commits,
                        "url" => "https://git.maineiac.dev/",
                        "timestamp" => date('c', $timestamp),
                        "color" => 16734720,
                        "footer" => [
                            "text" => $repo,
                            "icon_url" => $logo
                        ],
                        "author" => [
                            "icon_url" => $sender_av,
                            "name" => $sender,
                        ],
                    ]
                ]
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            logMessage("Ignoring push event with no commits");
        }
    } else {
        logMessage("Unhandled event type");
        http_response_code(200); // Acknowledge receipt of the webhook
        exit('Unhandled event type');
    }

    if ($json_data) {
        // Send the payload to the appropriate Discord webhook(s)
        $discordWebhook = $discord_webhooks[$repoName] ?? null;
        if (!$discordWebhook) {
            logMessage("No Discord webhook configured for repository: $repoName");
            http_response_code(500);
            exit('No Discord webhook configured for this repository');
        }

        sendToDiscordWebhooks($discordWebhook, $json_data);

        logMessage("Payload processed successfully");
        http_response_code(200);
        exit('Payload processed');
    }
}

logMessage("Invalid request URI");
http_response_code(404);
exit('Not found');
?>