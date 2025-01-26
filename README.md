# GSC Analytics for Posts

![WordPress](https://img.shields.io/badge/WordPress-6.3+-blue.svg)
![PHP](https://img.shields.io/badge/PHP-%3E%3D%207.4-8892BF.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

**GSC Analytics for Posts** is a powerful WordPress plugin that integrates with Google Search Console to fetch and display performance metrics for your posts. Gain insights into impressions, clicks, and average position directly from your WordPress dashboard.

## 🎯 Features

- 📊 **Post Analytics Dashboard**: View impressions, clicks, and average positions for each post.
- 🔍 **Query Analysis**: Analyze top search queries for specific URLs.
- 📈 **Query Clusters**: Automatically generate clusters based on similar search queries.
- 🎨 **Customizable Filters**: Filter results by impressions, clicks, and average position.
- 🔄 **Update Mechanism**: Seamless plugin updates via GitHub.

---

## 🚀 Getting Started

### 1. **Installation**
#### Using the WordPress Dashboard
1. Download the latest release ZIP file from the [Releases](https://github.com/nikhilarokkam/gsc-analytics-for-posts/releases) page.
2. Navigate to `Plugins > Add New > Upload Plugin`.
3. Upload the ZIP file and activate the plugin.

#### Manual Installation
1. Clone this repository or download the ZIP.
2. Extract the contents to the `wp-content/plugins/` directory of your WordPress installation.
3. Activate the plugin in the WordPress admin dashboard under `Plugins`.

---

### 2. **Configuration**
#### Step 1: Generate Google Search Console Credentials
1. Visit the [Google Cloud Console](https://console.cloud.google.com/).
2. Create a project and enable the **Google Search Console API**.
3. Generate a service account key in JSON format.
4. Save the JSON file as `gsc-credentials.json` in the root of the plugin directory.

#### Step 2: Configure the Plugin
1. Navigate to `GSC Analytics > Settings` in the WordPress admin dashboard.
2. Enter your Google Search Console property URL (e.g., `https://example.com/`).
3. Save your changes.

---

## 🛠️ Development

### Local Setup
1. Clone the repository:
   ```bash
   git clone https://github.com/nikhilarokkam/gsc-analytics-for-posts.git
   cd gsc-analytics-for-posts

2. Install Dependencies:
   ```bash
   composer install
   
3. Edit the `gsc-credentials.json` file to include your Google Search Console credentials.

---

## 📦 Automatic Updates
This plugin supports seamless updates using the [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) library. To ensure updates work correctly:

1. Maintain a valid `version.json` file in the GitHub repository.
2. The file must include:
- `version`: Plugin version (e.g., `2.2`).
- `download_url`: URL to the latest plugin ZIP file.
- `changelog`: Details about the update.
Example `version.json`:
 ```bash
{
    "version": "2.2",
    "download_url": "https://github.com/nikhilarokkam/gsc-analytics-for-posts/archive/refs/heads/main.zip",
    "changelog": "Fixed bugs and added improvements to the update mechanism."
}
