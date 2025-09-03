<?php

use PHPUnit\Framework\TestCase;
use EmDailyPostsQueue\init_plugin\Classes\EmDailyPostsQueueUIManager;
use EmDailyPostsQueue\init_plugin\Classes\PhotoNetSubmissionUtils;

class EmDailyPostsQueueUIManagerTest extends TestCase
{
    public function testCanInstantiateQueueManager()
    {
        $manager = new EmDailyPostsQueueUIManager();
        $this->assertInstanceOf(EmDailyPostsQueueUIManager::class, $manager);
    }

public function testQueueListIsArray()
{
    $manager = new EmDailyPostsQueueUIManager();
    // Mock the helper if needed
    if (!method_exists($manager, 'get_queue_list')) {
        $mockUtils = $this->getMockBuilder(PhotoNetSubmissionUtils::class)
            ->onlyMethods(['get_queue_list'])
            ->getMock();
        $mockUtils->method('get_queue_list')->willReturn([]);
        // Use Reflection to set private property
        $reflection = new \ReflectionClass($manager);
        $property = $reflection->getProperty('utils');
        $property->setAccessible(true);
        $property->setValue($manager, $mockUtils);
    }
    // Access via Reflection if needed
    $reflection = new \ReflectionClass($manager);
    $property = $reflection->getProperty('utils');
    $property->setAccessible(true);
    $utils = $property->getValue($manager);
    $queue_list = $utils->get_queue_list();
    $this->assertIsArray($queue_list);
}

    public function testAdminQueueEditPageMethodExists()
    {
        $manager = new EmDailyPostsQueueUIManager();
        $this->assertTrue(
            method_exists($manager, 'edpqadmin_queue_edit_page'),
            'edpqadmin_queue_edit_page method does not exist'
        );
    }

    public function testAdminQueueListPageMethodExists()
    {
        $manager = new EmDailyPostsQueueUIManager();
        $this->assertTrue(
            method_exists($manager, 'edpqadmin_queue_list_page'),
            'edpqadmin_queue_list_page method does not exist'
        );
    }

    public function testRemoveBulkActionsRemovesEditAndTrash()
    {
        $manager = new EmDailyPostsQueueUIManager();
        $actions = ['edit' => 'Edit', 'trash' => 'Trash', 'custom' => 'Custom'];
        $filtered = $manager->remove_bulk_actions($actions);
        $this->assertArrayNotHasKey('edit', $filtered);
        $this->assertArrayNotHasKey('trash', $filtered);
        $this->assertArrayHasKey('custom', $filtered);
    }

    public function testMyCptRowActionsRemovesQuickEditAndTrash()
    {
        $manager = new EmDailyPostsQueueUIManager();
        $actions = [
            'inline hide-if-no-js' => 'Quick Edit',
            'trash' => 'Trash',
            'bulk_edit' => 'Bulk Edit',
            'view' => 'View'
        ];
        $post = (object)['post_type' => 'net_submission'];
        $filtered = $manager->my_cpt_row_actions($actions, $post);
        $this->assertArrayNotHasKey('inline hide-if-no-js', $filtered);
        $this->assertArrayNotHasKey('trash', $filtered);
        $this->assertArrayNotHasKey('bulk_edit', $filtered);
        $this->assertArrayHasKey('view', $filtered);
    }

    public function testImportDemoNetSubmissionsExists()
    {
        $utils = new PhotoNetSubmissionUtils();
        $this->assertTrue(
            method_exists($utils, 'import_demo_net_submissions'),
            'import_demo_net_submissions method does not exist'
        );
    }

    public function testCompareMultiDimensionalReturnsArray()
    {
        $utils = new PhotoNetSubmissionUtils();
        $array1 = ['a' => 1, 'b' => ['c' => 2]];
        $array2 = ['a' => 1, 'b' => ['c' => 3]];
        $result = $utils->edpqcompareMultiDimensional($array1, $array2);
        $this->assertIsArray($result);
    }
}