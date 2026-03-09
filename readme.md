# Esmond Daily Posts Queue

**Project:** [https://github.com/Esmond-M/em-daily-posts-queue](https://github.com/Esmond-M/em-daily-posts-queue)  
**Author:** [esmondmccain.com](https://esmondmccain.com/)  
**Version:** 0.1.2

## Summary

Originally made for an intranet website. Packaged as a reusable plugin. Allows daily posts to be displayed on the front end via shortcode. Visitors submit photos through a front-end form; a custom admin role can then review, reorder, and manage the queue. The top post in the queue is automatically rotated out on a weekday schedule using Action Scheduler.

## Requirements

- **WordPress:** 6.1+
- **PHP:** 7.4+
- **Required Plugin:** [Action Scheduler](https://actionscheduler.org/)

## Quick Start — Shortcodes

After activation, go to **Net Submissions → 📋 Shortcodes** in the WP admin sidebar, or check the **Daily Posts Queue** widget on the main dashboard. Both show copy buttons for each shortcode.

| Shortcode | Where to use |
|---|---|
| `[EmDailyPostsQueueForm]` | Any page where visitors submit photos |
| `[EmDailyPostsQueueDisplayPost]` | Any page/widget area to show today's post |

Both shortcodes accept an optional `class` attribute:
```
[EmDailyPostsQueueForm class="my-wrapper"]
[EmDailyPostsQueueDisplayPost class="my-wrapper"]
```

## Features

- **Photo submission form** — front-end shortcode form; visitors upload a photo, headline, and caption (max 8 MB, JPG/PNG)
- **Daily post display** — shortcode that renders the current first-in-queue post (image, headline, caption)
- **Drag-and-drop queue management** — admin sub-menu to reorder or remove submissions
- **Automatic daily rotation** — Action Scheduler advances the queue every weekday; admins can customise the trigger time from the WP timezone settings
- **Demo content import** — one-click button on the queue edit page to seed 4 sample posts
- **Custom post type** `net_submission` — separate from regular posts; supports title and featured image
- **Custom role** `Net Submitter` — limited access; can submit and view own submissions only
- **Shortcode reference** — dedicated admin sub-menu page + dashboard widget so shortcodes are always visible
- **Optimistic concurrency** — queue edits check for stale data and warn before overwriting
- **JSON queue storage** — queue stored as JSON (migrated transparently from legacy serialize+base64)

## Installation

1. Download the latest zip from [build/em-daily-posts-queue.zip](https://github.com/Esmond-M/em-daily-posts-queue/blob/main/build/em-daily-posts-queue.zip).
2. In WordPress admin go to **Plugins → Add New → Upload Plugin**.
3. Upload the zip and activate.
4. Install and activate the **Action Scheduler** plugin.
5. Find your shortcodes under **Net Submissions → 📋 Shortcodes**.

![Queue List](/docs/imgs/queue-list.png "Queue List")

## Usage

### 1. Add the submission form to a page
```
[EmDailyPostsQueueForm]
```
Visitors fill in a headline, caption, and photo. On submission an email is sent to the site admin.

### 2. Display the current daily post
```
[EmDailyPostsQueueDisplayPost]
```
Shows the featured image, headline, and caption of the first item in the queue.

### 3. Manage the queue
1. Go to **Net Submissions → Edit Photo Queue**
2. Reorder items by changing the queue number fields and saving
3. Delete individual items with the remove button
4. Import demo content with the **Import Demo** button
5. Adjust the daily rotation time under the cron settings panel

### 4. Review submissions
Go to **Net Submissions** to see all submitted posts. Publishing a post automatically appends it to the end of the queue.

## Technical Details

### Database
| Table | Purpose |
|---|---|
| `{prefix}edpq_net_photos_queue_order` | Stores queue order as a JSON array of `{postid, queueNumber}` objects |

### Meta Fields
| Key | Description |
|---|---|
| `topic_headline_value` | Submission headline |
| `topic_caption_value` | Submission caption |

### Custom Post Type
- **Slug:** `net_submission`
- **Supports:** title, thumbnail
- **Custom capabilities:** `edit_net_submission`, `read_net_submission`, `delete_net_submission`, etc.

### User Roles
- **Net Submitter** — can create/read own `net_submission` posts and upload files; no access to queue management
- **Administrator** — full access including queue edit, wipe, and cron controls

### Shortcodes
| Shortcode | Class | Description |
|---|---|---|
| `[EmDailyPostsQueueForm]` | `EmDailyPostsQueue\init_plugin\Classes\Shortcodes` | Renders the photo submission form |
| `[EmDailyPostsQueueDisplayPost]` | `EmDailyPostsQueue\init_plugin\Classes\Shortcodes` | Renders the current daily post |

## API Hooks

### Actions
| Hook | Description |
|---|---|
| `init` | CPT registration, role setup, cron scheduling |
| `add_meta_boxes` | Custom meta box registration |
| `save_post` | Saves headline/caption meta |
| `publish_net_submission` | Appends post to queue on first publish |
| `trashed_post` | Force-deletes instead of trashing |
| `eg_1_weekdays_log` | Action Scheduler hook that advances the queue |

### Filters
| Hook | Description |
|---|---|
| `post_row_actions` | Removes Quick Edit and Trash from submission list |
| `bulk_actions-edit-net_submission` | Removes bulk edit action |
| `template_include` | Loads plugin template for single `net_submission` view |

## Development Setup

### Prerequisites
- WordPress local environment with database access
- Composer
- PHPUnit (included via composer)
- Node.js + npm (for `plugin-zip` build script)

### Setup

```bash
git clone https://github.com/Esmond-M/em-daily-posts-queue.git
cd em-daily-posts-queue
composer install
```

Configure `tests/wp-config.php`:
```php
define( 'DB_NAME',     'wordpress_test' );
define( 'DB_USER',     'your_username' );
define( 'DB_PASSWORD', 'your_password' );
define( 'DB_HOST',     'localhost' );
```

### Build zip

```bash
npm run plugin-zip
# Output: build/em-daily-posts-queue.zip
```

### File Structure
```
em-daily-posts-queue/
├── admin/assets/           # Admin-only CSS and JS
├── assets/                 # Frontend CSS, JS, images
├── classes/
│   ├── class-cpt-net-submission.php          # CPT registration + roles
│   ├── class-cpt-net-submission-meta.php     # Meta box (headline, caption)
│   ├── class-cron-event-timer.php            # Action Scheduler scheduling
│   ├── class-cron-events.php                 # Weekly queue rotation logic
│   ├── class-photo-submission-ajax.php       # All AJAX handlers
│   ├── class-photo-submission-queue-manager.php  # Main admin controller
│   ├── class-photo-submission-utils.php      # DB helpers, queue encode/decode
│   └── class-shortcodes.php                  # Frontend shortcode renderers
├── templates/
│   ├── options-page-admin-queue-edit.php     # Queue reorder UI
│   ├── options-page-admin-queue-list.php     # Queue read-only list
│   ├── shortcode-reference.php               # Shortcode reference card
│   └── single-net-submission.php             # Single post template
├── tests/
│   ├── bootstrap.php
│   ├── EmDailyPostsQueueUIManagerTest.php
│   └── wp-config.php
├── docs/
├── vendor/
├── composer.json
├── phpunit.xml
├── package.json
├── em-daily-posts-queue.php   # Plugin entry point
└── readme.md
```

## Testing

```bash
# Run all tests
.\vendor\bin\phpunit --bootstrap tests/bootstrap.php tests

# Run with verbose output
.\vendor\bin\phpunit --bootstrap tests/bootstrap.php tests --verbose
```

### Coverage areas
- CPT registration, roles, capabilities
- Meta box rendering and save
- Queue array comparison and conflict detection
- Cron scheduling and queue rotation
- Shortcode registration
- AJAX handler security (nonce, capability checks)

## Changelog

### Version 0.1.2
- **Security:** Replaced `serialize`/`base64` queue storage with `json_encode`; eliminates PHP object injection risk
- **Security:** `net_photo_deletion_info_ajax` no longer registered as `nopriv`; added nonce and capability checks to AJAX handlers
- **Security:** Sanitised all `$_POST` inputs (`sanitize_text_field`, `sanitize_textarea_field`, `(int)` casts)
- **Security:** `$wpdb->prepare()` used consistently; removed one unparameterised `UPDATE` query
- **Feature:** Added **Shortcodes** submenu page (📋) under Net Submissions CPT menu
- **Feature:** Added dashboard widget showing both shortcodes with copy-to-clipboard buttons
- **Bug fix:** Fatal `TypeError: Cannot access offset on string` on queue list page when DB contained the install greeting row
- **Bug fix:** `get_queue_list_from_db` and `get_queue_list` were identical; unified via `decode_queue()` helper with legacy migration
- **Bug fix:** `new CronEvents` at file scope removed; instantiation moved to plugin bootstrap only
- **Bug fix:** `wp_set_current_user()` hack replaced with direct `admin_url()` call for edit links

### Version 0.1.1
- Queue system now guarantees sequential queue numbers (no gaps)
- Changed admin queue container class name for uniqueness
- Removed bulk edit options for net_submission post type
- Added demo content import button to admin queue edit page
- Added cron event time update and display using WordPress timezone
- Added stubs for Action Scheduler functions to prevent IDE warnings
- Disabled submit buttons during form submission for better UX

### Version 0.1.0
- Initial release
- Custom post type and user role creation
- Frontend form and display shortcodes
- Queue management system
- Cron-based automatic posting
- Comprehensive test suite

## Support

For issues and feature requests, please visit the [GitHub repository](https://github.com/Esmond-M/em-daily-posts-queue/issues).

## License

This plugin is licensed under GPL