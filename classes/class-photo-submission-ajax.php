<?php
declare(strict_types=1);
namespace EmDailyPostsQueue\init_plugin\Classes;

class PhotoNetSubmissionAjax {
    /**
     * @var PhotoNetSubmissionUtils
     */
    private $utils;

    public function __construct() {
        $this->utils = new PhotoNetSubmissionUtils();
        // Register AJAX hooks here as you move each method
    }

    // Move AJAX methods here one by one
}
