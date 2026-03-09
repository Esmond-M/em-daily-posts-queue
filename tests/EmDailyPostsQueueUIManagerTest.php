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

    // ------------------------------------------------------------------
    // decode_queue (private) — tested via Reflection
    // ------------------------------------------------------------------

    public function testDecodeQueueEmptyStringReturnsEmptyArray()
    {
        $utils = new PhotoNetSubmissionUtils();
        $method = (new \ReflectionClass($utils))->getMethod('decode_queue');
        $method->setAccessible(true);
        $result = $method->invoke($utils, '');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testDecodeQueueValidJsonReturnsItems()
    {
        $utils = new PhotoNetSubmissionUtils();
        $method = (new \ReflectionClass($utils))->getMethod('decode_queue');
        $method->setAccessible(true);
        $queue = [['postid' => 1, 'queueNumber' => 1], ['postid' => 2, 'queueNumber' => 2]];
        $result = $method->invoke($utils, json_encode($queue));
        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]['postid']);
    }

    public function testDecodeQueueFiltersInstallGreetingRow()
    {
        $utils = new PhotoNetSubmissionUtils();
        $method = (new \ReflectionClass($utils))->getMethod('decode_queue');
        $method->setAccessible(true);
        // Install row: valid JSON but no postid/queueNumber keys
        $result = $method->invoke($utils, json_encode(['message' => 'Congratulations...']));
        $this->assertEmpty($result);
    }

    public function testDecodeQueueLegacyBase64SerializeIsAccepted()
    {
        $utils = new PhotoNetSubmissionUtils();
        $method = (new \ReflectionClass($utils))->getMethod('decode_queue');
        $method->setAccessible(true);
        $queue  = [['postid' => 5, 'queueNumber' => 1]];
        $legacy = base64_encode(serialize($queue));
        $result = $method->invoke($utils, $legacy);
        $this->assertCount(1, $result);
        $this->assertEquals(5, $result[0]['postid']);
    }

    public function testDecodeQueueCompletelyInvalidStringReturnsEmpty()
    {
        $utils = new PhotoNetSubmissionUtils();
        $method = (new \ReflectionClass($utils))->getMethod('decode_queue');
        $method->setAccessible(true);
        $result = $method->invoke($utils, 'not-json-not-base64-garbage!!!');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ------------------------------------------------------------------
    // filter_queue_items (private) — tested via Reflection
    // ------------------------------------------------------------------

    public function testFilterQueueItemsKeepsValidItems()
    {
        $utils  = new PhotoNetSubmissionUtils();
        $method = (new \ReflectionClass($utils))->getMethod('filter_queue_items');
        $method->setAccessible(true);
        $items  = [
            ['postid' => 1, 'queueNumber' => 1],
            ['postid' => 2, 'queueNumber' => 2],
        ];
        $result = $method->invoke($utils, $items);
        $this->assertCount(2, $result);
    }

    public function testFilterQueueItemsRemovesItemMissingPostid()
    {
        $utils  = new PhotoNetSubmissionUtils();
        $method = (new \ReflectionClass($utils))->getMethod('filter_queue_items');
        $method->setAccessible(true);
        $items  = [
            ['queueNumber' => 1],                     // missing postid
            ['postid' => 2, 'queueNumber' => 2],
        ];
        $result = $method->invoke($utils, $items);
        $this->assertCount(1, $result);
        $this->assertEquals(2, $result[0]['postid']);
    }

    public function testFilterQueueItemsRemovesItemMissingQueueNumber()
    {
        $utils  = new PhotoNetSubmissionUtils();
        $method = (new \ReflectionClass($utils))->getMethod('filter_queue_items');
        $method->setAccessible(true);
        $items  = [
            ['postid' => 1],                          // missing queueNumber
            ['postid' => 2, 'queueNumber' => 1],
        ];
        $result = $method->invoke($utils, $items);
        $this->assertCount(1, $result);
        $this->assertEquals(2, $result[0]['postid']);
    }

    public function testFilterQueueItemsRemovesNonArrayValues()
    {
        $utils  = new PhotoNetSubmissionUtils();
        $method = (new \ReflectionClass($utils))->getMethod('filter_queue_items');
        $method->setAccessible(true);
        $items  = [
            'message'  => 'Congratulations...',       // string scalar — install row pattern
            ['postid' => 1, 'queueNumber' => 1],
        ];
        $result = $method->invoke($utils, $items);
        $this->assertCount(1, $result);
    }

    public function testFilterQueueItemsEmptyInputReturnsEmpty()
    {
        $utils  = new PhotoNetSubmissionUtils();
        $method = (new \ReflectionClass($utils))->getMethod('filter_queue_items');
        $method->setAccessible(true);
        $result = $method->invoke($utils, []);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ------------------------------------------------------------------
    // New EmDailyPostsQueueUIManager methods — existence checks
    // ------------------------------------------------------------------

    public function testShortcodesPageMethodExists()
    {
        $manager = new EmDailyPostsQueueUIManager();
        $this->assertTrue(
            method_exists($manager, 'edpq_shortcodes_page'),
            'edpq_shortcodes_page method does not exist'
        );
    }

    public function testDashboardWidgetRegisterMethodExists()
    {
        $manager = new EmDailyPostsQueueUIManager();
        $this->assertTrue(
            method_exists($manager, 'edpq_register_dashboard_widget'),
            'edpq_register_dashboard_widget method does not exist'
        );
    }

    public function testDashboardWidgetRenderMethodExists()
    {
        $manager = new EmDailyPostsQueueUIManager();
        $this->assertTrue(
            method_exists($manager, 'edpq_dashboard_widget_render'),
            'edpq_dashboard_widget_render method does not exist'
        );
    }
}