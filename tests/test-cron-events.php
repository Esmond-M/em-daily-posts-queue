<?php

/**
 * Test cron events functionality
 */
class TestCronEvents extends WP_UnitTestCase
{
    private $cron_instance;

    /**
     * Set up the test environment
     */
    public function setUp(): void
    {
        parent::setUp();
        
        // Include the class file
        require_once __DIR__ . '/../classes/edpq-class-cron-events.php';
        
        // Create an instance of the class for testing
        $this->cron_instance = new \EmDailyPostsQueue\init_plugin\Classes\initCronEvents();
    }

    /**
     * Test multidimensional array comparison with identical arrays
     */
    public function test_multidimensional_array_comparison(): void
    {
        $array1 = [
            'key1' => 'value1',
            'key2' => [
                'nested1' => 'nested_value1',
                'nested2' => 'nested_value2'
            ],
            'key3' => 123
        ];

        $array2 = [
            'key1' => 'value1',
            'key2' => [
                'nested1' => 'nested_value1',
                'nested2' => 'nested_value2'
            ],
            'key3' => 123
        ];

        $result = $this->cron_instance->edpqcompareMultiDimensional($array1, $array2);
        
        $this->assertEmpty($result, 'Identical arrays should return empty result.');
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Multidimensional array comparison works correctly.\033[0m\n");
    }

    /**
     * Test array comparison with different values
     */
    public function test_array_comparison_with_differences(): void
    {
        $array1 = [
            'key1' => 'value1',
            'key2' => 'different_value',
            'key3' => 123
        ];

        $array2 = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 123
        ];

        $result = $this->cron_instance->edpqcompareMultiDimensional($array1, $array2);
        
        $this->assertNotEmpty($result, 'Different arrays should return non-empty result.');
        $this->assertEquals('different_value', $result['key2'], 'Difference should be captured correctly.');
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Array differences detected correctly.\033[0m\n");
    }

    /**
     * Test array comparison with empty input
     */
    public function test_array_with_empty_input(): void
    {
        $array1 = ['key1' => 'value1'];
        $array2 = [];

        $result = $this->cron_instance->edpqcompareMultiDimensional($array1, $array2);
        
        $this->assertEquals($array1, $result, 'Non-array second parameter should return first array.');
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Empty array input handled correctly.\033[0m\n");
    }

    /**
     * Test exception handling for non-array parameter
     */
    public function test_non_array_parameter_handling(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$array1 must be an array!');
        
        $this->cron_instance->edpqcompareMultiDimensional('not_an_array', []);
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Exception thrown for non-array parameter.\033[0m\n");
    }

    /**
     * Test float comparison handling
     */
    public function test_float_comparison(): void
    {
        $array1 = ['price' => 10.50];
        $array2 = ['price' => 10.50];

        $result = $this->cron_instance->edpqcompareMultiDimensional($array1, $array2);
        
        $this->assertEmpty($result, 'Identical float values should return empty result.');
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Float comparison works correctly.\033[0m\n");
    }

    /**
     * Test nested array differences
     */
    public function test_nested_array_differences(): void
    {
        $array1 = [
            'parent' => [
                'child1' => 'value1',
                'child2' => 'old_value'
            ]
        ];

        $array2 = [
            'parent' => [
                'child1' => 'value1',
                'child2' => 'new_value'
            ]
        ];

        $result = $this->cron_instance->edpqcompareMultiDimensional($array1, $array2);
        
        $this->assertNotEmpty($result, 'Nested differences should be detected.');
        $this->assertEquals('old_value', $result['parent']['child2'], 'Nested difference should be captured.');
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Nested array differences detected correctly.\033[0m\n");
    }
}