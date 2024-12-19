<?php
// Configurations
$discordWebhook = $_POST['webhook'] ?? null; // Discord webhook URL
$authorSteamID = $_POST['author_id'] ?? null; // SteamID of the user
$authorName = $_POST['author'] ?? "Unknown Author"; // Author name
$messageBody = $_POST['message'] ?? ""; // The body of the message (may contain image references)
$imageBaseURL = "https://api.akulla.dev/public/uploads/"; // Base URL for images
$steamAPIKey = "C20439FDF3895553DC8BE690AF976E24"; // Your Steam API Key
$fillerAuthorIcon = "https://img.xylos.gg/aphone/author_icon.png"; // Default icon for the author

// Validate required parameters
if (!$discordWebhook || !$authorSteamID || !$messageBody) {
    http_response_code(400);
    die("Missing required fields.");
}

// Convert SteamID to SteamID64
function convertSteamIDTo64($steamID) {
    if (preg_match('/^STEAM_0:([0-1]):([0-9]+)$/', $steamID, $matches)) {
        $authServer = $matches[1];
        $authID = $matches[2];
        $steamID64 = bcadd(bcmul($authID, '2'), bcadd('76561197960265728', $authServer));
        return $steamID64;
    }
    return null; // Invalid SteamID format
}

// Get the Steam avatar URL
function getSteamAvatar($steamID, $apiKey, $fallbackIcon) {
    $steamID64 = convertSteamIDTo64($steamID);
    if (!$steamID64) {
        return $fallbackIcon; // Return fallback icon if SteamID conversion fails
    }

    $apiURL = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/?key=$apiKey&steamids=$steamID64";
    $response = @file_get_contents($apiURL);
    if ($response) {
        $data = json_decode($response, true);
        if (!empty($data['response']['players'][0]['avatar'])) {
            return $data['response']['players'][0]['avatar'];
        }
    }

    return $fallbackIcon; // Return fallback icon if API call fails
}

// Extract image reference from the message (if applicable)
function extractImageCode($message) {
    if (preg_match('/^imgur:\/\/([a-zA-Z0-9_]+)/', $message, $matches)) {
        return $matches[1]; // Extract the image code
    }
    return null; // No image code found
}

// Generate embed payload for Discord
function createDiscordEmbed($authorName, $authorIcon, $messageBody, $steamID, $imageURL = null) {
    $embed = [
        "author" => [
            "name" => $authorName,
            "icon_url" => $authorIcon
        ],
        "color" => 3447003, // Optional embed color
        "footer" => [
            "text" => $steamID
        ],
        "timestamp" => date(DATE_ISO8601) // Embed timestamp
    ];

    // Add description if it's a text message
    if ($messageBody && !$imageURL) {
        $embed["description"] = $messageBody;
    }

    // Add image if an image URL is provided
    if ($imageURL) {
        $embed["image"] = [
            "url" => $imageURL
        ];
    }

    return $embed;
}

// Main logic
$authorIcon = getSteamAvatar($authorSteamID, $steamAPIKey, $fillerAuthorIcon);
$imageCode = extractImageCode($messageBody); // Extract the image code from the message
$imageURL = $imageCode ? $imageBaseURL . $imageCode . ".jpg" : null; // Generate full image URL

// If an image is found, remove the image reference from the message
if ($imageCode) {
    $messageBody = ""; // Clear message body since it's an image-only post
}

// Create the embed payload
$embed = createDiscordEmbed($authorName, $authorIcon, $messageBody, $authorSteamID, $imageURL);

// Send payload to Discord
$payload = json_encode(["embeds" => [$embed]]);
$ch = curl_init($discordWebhook);

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Respond with appropriate status
if ($httpCode === 204) {
    http_response_code(200);
    echo "Message sent successfully.";
} else {
    http_response_code(500);
    echo "Failed to send the message to Discord.";
}
