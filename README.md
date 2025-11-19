# WP Media Cleaner

A lightweight, professional WordPress library designed to identify and remove orphaned media attachments (files with no parent post). This tool helps developers and site administrators save server space and keep the media library clean.

## Features

- **Orphan Detection:** Scans the database for media attachments where `post_parent` is 0.
- **Safe Deletion:** Provides a visual interface to review files before permanent deletion.
- **Batch Processing:** Allows selecting multiple files for bulk removal.
- **Theme Integration:** Designed to be dropped directly into a theme structure without plugin overhead.
- **Security:** Implements WordPress Nonces and capability checks (`manage_options`).

## Installation

1. **Download:** Clone or download this repository.
2. **Copy:** Place the `wp-media-cleaner` folder into your theme's library directory (e.g., `/wp-content/themes/your-theme/inc/`).
3. **Include:** Add the following line to your theme's `functions.php` file:

```php
// Adjust the path based on where you placed the folder
require_once get_template_directory() . '/inc/wp-media-cleane/loader.php';
 ```

## Usage

1. Log in to the WordPress Admin Dashboard.
2. Navigate to `Tools > Media Cleaner`.
3. Review the list of orphaned media files.
4. Select the files you wish to remove.
5. Click Delete Selected Permanently.
