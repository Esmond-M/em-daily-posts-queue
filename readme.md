# Esmond Daily Posts Queue

**Project:** [https://github.com/Esmond-M/em-daily-posts-queue](https://github.com/Esmond-M/em-daily-posts-queue)  
**Author:** [esmondmccain.com](https://esmondmccain.com/)  
**Version:** 0.1.0  

## Summary

Originally made for AWC intranet website. Being put into a plugin for future use. This plugin allows for daily posts to be displayed on the front-end. These posts can be submitted to the website through a short-code form on the front-end. I have also made a custom role on the back-end for a user that will edit these posts. The role will have access to reordering the release of the daily post or even choosing not to publish the post if submitted by a user.

## Requirements

- **WordPress:** 6.1+
- **PHP:** 7.4.33+
- **Required Plugin:** Action Scheduler

## Features & Guidelines

• This plugin is used in conjunction with the "Action Scheduler" plugin. The number one post in the queue will get deleted once every weekday. The next post will then move up and be displayed on the front-end using the short-code `[EmDailyPostsQueueDisplayPost]`.  
• This plugin adds a Custom Post type "Net Submissions".  
• Adds WordPress user role "Net Submitter". Only this role and administrators can edit the new post type and queue system.  
• Short-code `[EmDailyPostsQueueForm]` for front-end form.  
• Short-code `[EmDailyPostsQueueDisplayPost]` for displaying the current daily post.  
• Once a post in this post type has been published it can only be deleted on the queue sub-menu page.

## Technical Details

### Database
- **Custom Table:** `edpq_net_photos_queue_order` - Stores the queue order and post relationships
- **Meta Fields:** `topic_headline_value`, `topic_caption_value` - Custom fields for net submissions

### Custom Post Type
- **Post Type:** `net_submission`
- **Capabilities:** Custom capability system with `edit_net_submission`, `read_net_submission`, etc.
- **Features:** Supports title and thumbnail

### User Roles
- **Custom Role:** `net_submission_role` (Net Submitter)
- **Capabilities:** Limited access to net submission posts and file uploads
- **Admin Enhancement:** Administrators get full access to all net submission capabilities

### Shortcodes
- `[EmDailyPostsQueueForm]` - Frontend submission form
- `[EmDailyPostsQueueDisplayPost]` - Display current daily post

## Installation

1. Download the latest version from [https://github.com/Esmond-M/em-daily-posts-queue/blob/main/build/em-daily-posts-queue.zip](https://github.com/Esmond-M/em-daily-posts-queue/blob/main/build/em-daily-posts-queue.zip).
2. Upload `em-daily-posts-queue` zip to the `/wp-content/plugins/` directory.
3. Extract zip folder. Folder name of plugin should be "em-daily-posts-queue".
4. Activate the plugin through the 'Plugins' menu in WordPress.
5. Ensure "Action Scheduler" plugin is installed and activated.

![Alt text](/docs/imgs/admin-menu.png "Admin Menu Option")

![Alt text](/docs/imgs/queue-list.png "Queue List")

## Development Setup

### Prerequisites
1. **WordPress Development Environment** with database access
2. **Composer** for dependency management
3. **PHPUnit** for testing (included via composer)

### Setup Steps
1. Clone the repository:
   ```bash
   git clone https://github.com/Esmond-M/em-daily-posts-queue.git
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Configure test database in `tests/wp-config.php`:
   ```php
   define( 'DB_NAME', 'wordpress_test' );
   define( 'DB_USER', 'your_username' );
   define( 'DB_PASSWORD', 'your_password' );
   define( 'DB_HOST', 'localhost' );
   ```

4. Set up WordPress test environment variables (if needed):
   ```bash
   export WP_PHPUNIT__DIR=/path/to/wordpress-develop/tests/phpunit
   ```

### File Structure
```
em-daily-posts-queue/
├── classes/                          # Main plugin classes
│   ├── cpt-net-submissions.php       # Custom post type registration
│   ├── cpt-meta-net-submissions.php  # Meta box functionality
│   ├── edpq-class-auto-photo-net-submissions.php  # Auto photo submissions
│   ├── edpq-class-cron-events.php    # Cron event handling
│   └── edpq-class-cron-event-timers.php  # Cron timing
├── tests/                            # PHPUnit test suite
│   ├── bootstrap.php                 # Test bootstrap
│   ├── wp-config.php                 # Test WordPress configuration
│   ├── test-user.php                 # User functionality tests
│   ├── test-cpt-net-submissions.php  # CPT registration tests
│   ├── test-cron-events.php          # Cron events tests
│   ├── test-plugin-main.php          # Main plugin tests
│   ├── test-auto-photo-submissions.php  # Auto photo tests
│   └── test-meta-boxes.php           # Meta box tests
├── docs/                             # Documentation and images
├── vendor/                           # Composer dependencies
├── composer.json                     # Composer configuration
├── phpunit.xml                       # PHPUnit configuration
├── em-daily-posts-queue.php          # Main plugin file
└── readme.md                         # This file
```

## Testing

This plugin includes comprehensive PHPUnit tests covering all major functionality.

### Running Tests

**Prerequisites:** Ensure WordPress and database are running.

```bash
# Run all tests
.\vendor\bin\phpunit --configuration phpunit.xml

# Run specific test file
.\vendor\bin\phpunit tests/test-cpt-net-submissions.php

# Run with verbose output
.\vendor\bin\phpunit --configuration phpunit.xml --verbose
```

### Test Coverage

- **Custom Post Type Registration** - CPT creation, roles, capabilities
- **User Roles and Capabilities** - Permission system testing
- **Meta Box Functionality** - Form handling, nonce verification, data sanitization
- **Cron Events and Queue Management** - Array comparison, queue processing
- **Plugin Initialization** - Singleton pattern, shortcode registration
- **Auto Photo Submissions** - Form processing, validation, security

### Test Categories

- **Happy Path** - Normal operation flows
- **Input Verification** - Edge cases and boundary values  
- **Exception Handling** - Error conditions and invalid inputs
- **Security Testing** - Nonce verification, permission checks, data escaping

## Usage

### Frontend Form
Add the submission form to any page or post:
```
[EmDailyPostsQueueForm class="your-css-class"]
```

### Display Daily Post
Show the current daily post:
```
[EmDailyPostsQueueDisplayPost class="your-css-class"]
```

### Backend Management
1. Navigate to **Net Submissions** in WordPress admin
2. Use the **Edit Net Submissions** sub-menu to manage the queue
3. Reorder posts by changing queue numbers
4. Remove posts from queue as needed

## API Hooks

### Actions
- `init` - Plugin initialization
- `add_meta_boxes` - Meta box registration
- `save_post` - Meta data saving
- `publish_net_submission` - Post publication handling
- `trashed_post` - Post deletion handling

### Filters
- `post_row_actions` - Custom row actions for net submissions

## Changelog

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

This plugin is licensed under GPL v2 or later.