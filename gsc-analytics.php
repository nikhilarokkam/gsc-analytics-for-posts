<?php
/**
 * Plugin Name: GSC Analytics for Posts
 * Description: Fetch impressions, clicks, and average position from Google Search Console for WordPress posts.
 * Author: Rokkam Nikhila
 */













require_once plugin_dir_path(__FILE__) . 'vendor/plugin-update-checker/plugin-update-checker.php';

$updateChecker = Puc_v4_Factory::buildUpdateChecker(
    'https://raw.githubusercontent.com/nikhilarokkam/gsc-analytics-for-posts/main/version.json',
    __FILE__,
    'gsc-analytics-for-posts'
);

$updateChecker->setCacheDuration(3600);

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
require_once plugin_dir_path(__FILE__) . 'admin/settings-page.php';

add_action('admin_menu', 'gsc_analytics_add_admin_menu');

function gsc_analytics_add_admin_menu() {
    add_menu_page(
        'GSC Analytics',
        'GSC Analytics',
        'manage_options',
        'gsc-analytics',
        'gsc_analytics_display_page',
        'dashicons-chart-line',
        20
    );

    add_submenu_page(
        'gsc-analytics',
        'Settings',
        'Settings',
        'manage_options',
        'gsc-settings',
        'gsc_settings_page'
    );

    add_submenu_page(
        'gsc-analytics',
        'Analyze URL Queries',
        'Analyze URL Queries',
        'manage_options',
        'gsc-analyze-url',
        'gsc_analyze_url_page'
    );
}

function gsc_analytics_display_page() {
    $analytics_data = gsc_fetch_analytics_data();

    if (empty($analytics_data)) {
        echo '<p style="color: red; text-align: center;">No data found. Ensure settings are configured correctly.</p>';
        return;
    }

    // Handle sorting and filtering
    $analytics_data = gsc_handle_sort_and_filter($analytics_data);

    $current_sort_by = $_GET['sort_by'] ?? 'title';
    $current_sort_order = $_GET['sort_order'] ?? 'asc';
    $next_sort_order = $current_sort_order === 'asc' ? 'desc' : 'asc';

    echo '<h1 style="text-align: center; padding-top: 20px; padding-bottom: 20px;">GSC Analytics for Posts</h1>';

    // Filters for impressions and position
    echo '<center><form method="get" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">';
    echo '<input type="hidden" name="page" value="gsc-analytics">';
    echo '<label style="margin-right: 5px;">Impressions:</label>';
    echo 'From <input type="number" name="filter_impressions_min" value="' . esc_attr($_GET['filter_impressions_min'] ?? '') . '" style="margin-right: 5px;">';
    echo 'To <input type="number" name="filter_impressions_max" value="' . esc_attr($_GET['filter_impressions_max'] ?? '') . '" style="margin-right: 15px;">';
    echo '<label style="margin-right: 5px;">Avg Position:</label>';
    echo 'From <input type="number" name="filter_position_min" value="' . esc_attr($_GET['filter_position_min'] ?? '') . '" style="margin-right: 5px;">';
    echo 'To <input type="number" name="filter_position_max" value="' . esc_attr($_GET['filter_position_max'] ?? '') . '" style="margin-right: 15px;">';
    echo '<button type="submit" style="padding: 5px 10px; background-color: #0073aa; color: #fff; border: none;">Apply</button>';
    echo '</form></center>';

    // Table header with sortable columns
    echo '<table style="width: 99%; border-collapse: collapse; margin-top: 50px; background-color: white; border-radius: 24px; overflow: hidden; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);">
    <thead style="background-color: #000000;">
        <tr>
            <th style="padding: 12px; text-align: center; text-transform: uppercase;"> 
                <a href="?page=gsc-analytics&sort_by=title&sort_order=' . $next_sort_order . '" style="text-decoration: none; color: #FFFFFF;">Post Title</a>
            </th>
            <th style="padding: 12px; text-align: left; text-transform: uppercase;">
                <a href="?page=gsc-analytics&sort_by=last_modified&sort_order=' . $next_sort_order . '" style="text-decoration: none; color: #FFFFFF;">Last Modified</a>
            </th>
            <th style="padding: 12px; text-align: left; text-transform: uppercase;">
                <a href="?page=gsc-analytics&sort_by=impressions&sort_order=' . $next_sort_order . '" style="text-decoration: none; color: #FFFFFF;">Impressions</a>
            </th>
            <th style="padding: 12px; text-align: left; text-transform: uppercase;">
                <a href="?page=gsc-analytics&sort_by=clicks&sort_order=' . $next_sort_order . '" style="text-decoration: none; color: #FFFFFF;">Clicks</a>
            </th>
            <th style="padding: 12px; text-align: left; text-transform: uppercase;">
                <a href="?page=gsc-analytics&sort_by=average_position&sort_order=' . $next_sort_order . '" style="text-decoration: none; color: #FFFFFF;">Average Position</a>
            </th>
        </tr>
    </thead>
    <tbody>';

    // Populate the table with analytics data
    foreach ($analytics_data as $data) {
        echo "<tr style='border-bottom: 1px solid transparent;'>
                <td style='padding: 12px;'>
                    <a href='" . esc_url(get_permalink($data['id'])) . "' target='_blank' style='text-decoration: none; color: #0073aa;'>{$data['title']}</a>
                </td>
                <td style='padding: 12px;'>{$data['last_modified']}</td>
                <td style='padding: 12px;'>{$data['impressions']}</td>
                <td style='padding: 12px;'>{$data['clicks']}</td>
                <td style='padding: 12px;'>{$data['average_position']}</td>
            </tr>";
    }

    echo '</tbody></table>';
}

function gsc_fetch_analytics_data() {
    $gsc_property = get_option('gsc_property', '');
    $credentials_path = plugin_dir_path(__FILE__) . 'gsc-credentials.json';

    if (empty($gsc_property) || !file_exists($credentials_path)) {
        return [];
    }

    $client = new Google_Client();
    $client->setAuthConfig($credentials_path);
    $client->addScope('https://www.googleapis.com/auth/webmasters.readonly');

    $service = new Google_Service_Webmasters($client);
    $analytics_data = [];
    $posts = get_posts(['post_type' => 'post', 'numberposts' => -1]);

    foreach ($posts as $post) {
        $url = get_permalink($post->ID);

        try {
            $startDate = date('Y-m-d', strtotime('-90 days'));
            $endDate = date('Y-m-d');
            $request = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
            $request->setStartDate($startDate);
            $request->setEndDate($endDate);
            $request->setDimensions(['page']);
            $request->setDimensionFilterGroups([
                [
                    'filters' => [
                        [
                            'dimension' => 'page',
                            'operator' => 'equals',
                            'expression' => $url,
                        ],
                    ],
                ],
            ]);

            $response = $service->searchanalytics->query($gsc_property, $request);

            $impressions = 0;
            $clicks = 0;
            $position_sum = 0;
            $position_count = 0;

            foreach ($response->getRows() as $row) {
                $impressions += $row->getImpressions();
                $clicks += $row->getClicks();
                $position_sum += $row->getPosition();
                $position_count++;
            }

            $average_position = $position_count > 0 ? $position_sum / $position_count : 0;

            $analytics_data[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'last_modified' => get_post_modified_time('F j, Y g:i a', false, $post->ID),
                'impressions' => $impressions,
                'clicks' => $clicks,
                'average_position' => round($average_position, 2),
            ];
        } catch (Exception $e) {
            error_log('GSC API Error: ' . $e->getMessage());
        }
    }

    return $analytics_data;
}

function gsc_handle_sort_and_filter($data) {
    $filter_impressions_min = $_GET['filter_impressions_min'] ?? null;
    $filter_impressions_max = $_GET['filter_impressions_max'] ?? null;
    $filter_position_min = $_GET['filter_position_min'] ?? null;
    $filter_position_max = $_GET['filter_position_max'] ?? null;

    // Apply filters
    $data = array_filter($data, function ($row) use (
        $filter_impressions_min, $filter_impressions_max, $filter_position_min, $filter_position_max
    ) {
        $impressions_pass = (!$filter_impressions_min || $row['impressions'] >= (int)$filter_impressions_min) &&
                            (!$filter_impressions_max || $row['impressions'] <= (int)$filter_impressions_max);

        $position_pass = (!$filter_position_min || $row['average_position'] >= (float)$filter_position_min) &&
                         (!$filter_position_max || $row['average_position'] <= (float)$filter_position_max);

        return $impressions_pass && $position_pass;
    });

    // Apply sorting
    $sort_by = $_GET['sort_by'] ?? 'title';
    $sort_order = $_GET['sort_order'] ?? 'asc';
    usort($data, function ($a, $b) use ($sort_by, $sort_order) {
        $valueA = $a[$sort_by] ?? '';
        $valueB = $b[$sort_by] ?? '';

        return $sort_order === 'asc' ? $valueA <=> $valueB : $valueB <=> $valueA;
    });

    return $data;
}

function gsc_analyze_url_page() {
    echo '<h1 style="text-align: center;">Analyze URL Queries & Generate Clusters</h1>';

    echo '<h2 style="text-align: center;">Analyze Queries for a Specific URL</h2>';
    echo '<center>
            <form method="post" style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 30px;">
                <label>Enter URL:</label>
                <input type="url" name="post_url" required style="padding: 5px; width: 300px;">
                <button type="submit" name="analyze_url" style="padding: 5px 15px; background-color: #0073aa; color: #fff; border: none;">Analyze</button>
            </form>
          </center>';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['analyze_url']) && !empty($_POST['post_url'])) {
        $post_url = esc_url_raw($_POST['post_url']);
        gsc_process_url_analysis($post_url);
    }

    echo '<h2 style="text-align: center; margin-top: 50px;">Generate Query Clusters</h2>';
    echo '<center>
            <form method="post" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="submit" name="analyze_clusters" style="padding: 10px 20px; background-color: #0073aa; color: #fff; border: none;">Generate Clusters</button>
            </form>
          </center>';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['analyze_clusters'])) {
        gsc_generate_clusters();
    }
}

function gsc_process_url_analysis($post_url) {
    $gsc_property = get_option('gsc_property', '');
    $credentials_path = plugin_dir_path(__FILE__) . 'gsc-credentials.json';

    if (empty($gsc_property) || !file_exists($credentials_path)) {
        echo '<p style="color: red; text-align: center;">Error: Missing Google Search Console settings or credentials file.</p>';
        return;
    }

    $client = new Google_Client();
    $client->setAuthConfig($credentials_path);
    $client->addScope('https://www.googleapis.com/auth/webmasters.readonly');

    $service = new Google_Service_Webmasters($client);

    try {
        $startDate = date('Y-m-d', strtotime('-90 days'));
        $endDate = date('Y-m-d');
        $request = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
        $request->setStartDate($startDate);
        $request->setEndDate($endDate);
        $request->setDimensions(['query']);
        $request->setDimensionFilterGroups([
            [
                'filters' => [
                    [
                        'dimension' => 'page',
                        'operator' => 'equals',
                        'expression' => $post_url,
                    ],
                ],
            ],
        ]);

        $response = $service->searchanalytics->query($gsc_property, $request);

        // Fetch the page content
        $page_content = file_get_contents($post_url);
        if (!$page_content) {
            echo '<p style="color: red; text-align: center;">Error: Unable to fetch the content of the page.</p>';
            return;
        }
        $page_content = strtolower(strip_tags($page_content)); // Lowercase and remove HTML tags

        if ($response->getRows()) {
            echo '<h3 style="text-align: center;">Top Queries for URL: ' . esc_html($post_url) . '</h3>';
            echo '<table style="width: 100%; border-collapse: collapse; margin-top: 20px; background-color: white; border-radius: 10px;">
                    <thead>
                        <tr style="background-color: #0073aa; color: #fff;">
                            <th style="padding: 10px;">Query</th>
                            <th style="padding: 10px; text-align: left;">Clicks</th>
                            <th style="padding: 10px; text-align: left;">Impressions</th>
                            <th style="padding: 10px; text-align: left;">CTR</th>
                            <th style="padding: 10px; text-align: left;">Position</th>
                            <th style="padding: 10px; text-align: left;">Frequency</th>
                        </tr>
                    </thead>
                    <tbody>';

            foreach ($response->getRows() as $row) {
                $query = esc_html($row->getKeys()[0]);
                $clicks = $row->getClicks();
                $impressions = $row->getImpressions();
                $ctr = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) . '%' : '0%';
                $position = round($row->getPosition(), 2);

                // Count frequency of the query in the page content
                $query_frequency = substr_count($page_content, strtolower($query));

                echo '<tr style="border-bottom: 1px solid #ddd;">
                        <td style="padding: 10px;">' . $query . '</td>
                        <td style="padding: 10px;">' . $clicks . '</td>
                        <td style="padding: 10px;">' . $impressions . '</td>
                        <td style="padding: 10px;">' . $ctr . '</td>
                        <td style="padding: 10px;">' . $position . '</td>
                        <td style="padding: 10px;">' . $query_frequency . '</td>
                      </tr>';
            }

            echo '</tbody></table>';
        } else {
            echo '<p style="color: red; text-align: center;">No query data found for this URL.</p>';
        }
    } catch (Exception $e) {
        echo '<p style="color: red; text-align: center;">Error: ' . $e->getMessage() . '</p>';
    }
}

function gsc_generate_clusters() {
    $gsc_property = get_option('gsc_property', '');
    $credentials_path = plugin_dir_path(__FILE__) . 'gsc-credentials.json';

    if (empty($gsc_property) || !file_exists($credentials_path)) {
        echo '<p style="color: red; text-align: center;">Error: Missing Google Search Console settings or credentials file.</p>';
        return;
    }

    $client = new Google_Client();
    $client->setAuthConfig($credentials_path);
    $client->addScope('https://www.googleapis.com/auth/webmasters.readonly');

    $service = new Google_Service_Webmasters($client);

    try {
        $startDate = date('Y-m-d', strtotime('-90 days'));
        $endDate = date('Y-m-d');
        $request = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
        $request->setStartDate($startDate);
        $request->setEndDate($endDate);
        $request->setDimensions(['query', 'page']);

        $response = $service->searchanalytics->query($gsc_property, $request);

        if ($response->getRows()) {
            $queries = [];
            foreach ($response->getRows() as $row) {
                $query = strtolower($row->getKeys()[0]);
                $page = $row->getKeys()[1];
                $queries[] = ['query' => $query, 'page' => $page];
            }

            $clusters = gsc_cluster_queries($queries);

            echo '<h2 style="text-align: center; margin-top: 30px;">Clusters</h2>';
            echo '<table style="width: 100%; border-collapse: collapse; margin-top: 20px; background-color: white; border-radius: 10px;">
                    <thead>
                        <tr style="background-color: #0073aa; color: #fff;">
                            <th style="padding: 10px;">Cluster</th>
                            <th style="padding: 10px;">Number of Queries</th>
                            <th style="padding: 10px;">Queries</th>
                            <th style="padding: 10px;">Landing Pages</th>
                        </tr>
                    </thead>
                    <tbody>';

            foreach ($clusters as $cluster_name => $cluster_data) {
                echo '<tr style="border-bottom: 1px solid #ddd;">
                        <td style="padding: 10px;">' . esc_html($cluster_name) . '</td>
                        <td style="padding: 10px;">' . count($cluster_data['queries']) . '</td>
                        <td style="padding: 10px;">' . implode(', ', $cluster_data['queries']) . '</td>
                        <td style="padding: 10px;">' . implode(', ', $cluster_data['pages']) . '</td>
                      </tr>';
            }

            echo '</tbody></table>';
        } else {
            echo '<p style="color: red; text-align: center;">No query data found.</p>';
        }
    } catch (Exception $e) {
        echo '<p style="color: red; text-align: center;">Error: ' . $e->getMessage() . '</p>';
    }
}

function gsc_cluster_queries($queries) {
    $clusters = [];

    foreach ($queries as $query_data) {
        $query = $query_data['query'];
        $page = $query_data['page'];

        $found_cluster = false;

        foreach ($clusters as $cluster_name => &$cluster_data) {
            similar_text($query, $cluster_name, $percent);
            if ($percent > 70) {
                $cluster_data['queries'][] = $query;
                $cluster_data['pages'][] = $page;
                $cluster_data['pages'] = array_unique($cluster_data['pages']);
                $found_cluster = true;
                break;
            }
        }

        if (!$found_cluster) {
            $clusters[$query] = [
                'queries' => [$query],
                'pages' => [$page]
            ];
        }
    }

    return $clusters;
}
?>
