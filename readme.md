Project: [https://github.com/Esmond-M/em-daily-posts-queue](https://github.com/Esmond-M/em-daily-posts-queue)<br>
Author: [esmondmccain.com](https://esmondmccain.com/)
## Summary
Originally made for AWC intranet website. Being put into a plugin for future use. This plugin allows for daily posts to be displayed on the front-end. These posts can be submitted to the website through a short-code form on the front-end. I have also made a custom role on the back-end for a user that will edit these posts. The role will have access to reordering the release of the daily post or even choosing not to publish the post if submitted by a user.
## Features & Guidelines
    • This plugin is used in conjunction with the "Action Scheduler" plugin. The number one post in the queue will get deleted once every weekday. The next post will then move up and be displayed on the front-end using the short-code “[EmDailyPostsQueueDisplayPost]”.
    • This plugin adds a Custom Post type "Net Submissions". 
    • Adds WordPress user role “Net Submitter”. Only this role and administrators can edit the new post type and queue system.
    • Short-code “[EmDailyPostsQueueForm]” for front-end form. 
    • Short-code “[EmDailyPostsQueueDisplayPost]” for displaying the current daily post.
    • Once a post in this post type has been published it can only be deleted on the queue sub-menu page.



 ## Installation

1. Download the latest version from [https://github.com/Esmond-M/em-daily-posts-queue/blob/main/build/em-daily-posts-queue.zip](https://github.com/Esmond-M/em-daily-posts-queue/blob/main/build/em-daily-posts-queue.zip).
2. Upload `em-daily-posts-queue` zip to the `/wp-content/plugins/` directory.
3. Extract zip folder. Folder name of plugin should be "em-daily-posts-queue".
4. Activate the plugin through the 'Plugins' menu in WordPress.



