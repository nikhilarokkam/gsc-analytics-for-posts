<?php
function gsc_settings_page() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gsc_save_settings'])) {
        if (isset($_POST['gsc_property'])) {
            $gsc_property = sanitize_text_field($_POST['gsc_property']);

            // Validate the input as either a URL or a domain
            if (!filter_var($gsc_property, FILTER_VALIDATE_URL) && 
                !preg_match('/^([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/', $gsc_property)) {
                echo '<p style="color: red;">Invalid GSC property. Enter a valid URL or domain.</p>';
            } else {
                update_option('gsc_property', $gsc_property);
                echo '<p style="color: green;">GSC Property saved successfully.</p>';
            }
        }

        if (!empty($_FILES['gsc_credentials']['name'])) {
            $uploaded_file = $_FILES['gsc_credentials'];
            $upload_dir = plugin_dir_path(__DIR__);
            $target_path = $upload_dir . 'gsc-credentials.json';

            if (move_uploaded_file($uploaded_file['tmp_name'], $target_path)) {
                echo '<p style="color: green;">JSON credentials file uploaded successfully to ' . $target_path . '.</p>';
            } else {
                echo '<p style="color: red;">Failed to upload the credentials file. Directory path: ' . $target_path . '</p>';
            }
        }

        echo '<p style="color: green;">Settings saved successfully.</p>';
    }

    $gsc_property = get_option('gsc_property', '');

    echo '<h1>GSC Analytics Settings</h1>';
    echo '<form method="post" enctype="multipart/form-data" style="max-width: 600px;">';
    echo '<label for="gsc_property">Google Search Console Property (URL or domain, e.g., https://example.com or example.com):</label><br>';
    echo '<input type="text" id="gsc_property" name="gsc_property" value="' . esc_attr($gsc_property) . '" style="width: 100%; margin-bottom: 20px;" required><br>';
    echo '<p style="font-size: 12px; color: #666; margin-bottom: 20px;">Enter the exact GSC property as shown in Google Search Console. Supports both URL and domain formats.</p>';
    echo '<label for="gsc_credentials">Upload JSON Credentials File:</label><br>';
    echo '<input type="file" id="gsc_credentials" name="gsc_credentials" accept=".json" style="margin-bottom: 20px;" required><br>';
    echo '<button type="submit" name="gsc_save_settings" style="padding: 10px 20px; background-color: #0073aa; color: #fff; border: none; cursor: pointer;">Save Settings</button>';
    echo '</form>';
}