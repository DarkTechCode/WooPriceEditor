<?php
/**
 * Tests for option defaults
 *
 * @package WooPriceEditor
 */

class Test_Option_Defaults extends WPE_Test_Case {
    
    /**
     * Test default options structure
     */
    public function test_default_options_structure() {
        $defaults = WPE_Plugin::get_default_options();
        
        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('start_category', $defaults);
        $this->assertArrayHasKey('default_columns', $defaults);
        $this->assertArrayHasKey('instructions', $defaults);
    }
    
    /**
     * Test default start_category value
     */
    public function test_default_start_category() {
        $defaults = WPE_Plugin::get_default_options();
        
        $this->assertEquals('all', $defaults['start_category']);
    }
    
    /**
     * Test default columns array
     */
    public function test_default_columns() {
        $defaults = WPE_Plugin::get_default_options();
        
        $this->assertIsArray($defaults['default_columns']);
        $this->assertNotEmpty($defaults['default_columns']);
        
        // Check expected default columns
        $expected_columns = [
            'sku',
            'regular_price',
            'sale_price',
            'stock_status',
            'tax_status',
        ];
        
        $this->assertEquals($expected_columns, $defaults['default_columns']);
    }
    
    /**
     * Test default instructions are not empty
     */
    public function test_default_instructions() {
        $defaults = WPE_Plugin::get_default_options();
        
        $this->assertIsString($defaults['instructions']);
        $this->assertNotEmpty($defaults['instructions']);
        $this->assertStringContainsString('Welcome to the Woo Price Editor', $defaults['instructions']);
    }
    
    /**
     * Test plugin activation creates options
     */
    public function test_activation_creates_options() {
        // Ensure option doesn't exist
        delete_option('wpe_editor_settings');
        $this->assertFalse(get_option('wpe_editor_settings'));
        
        // Trigger activation
        WPE_Plugin::activate();
        
        // Check option was created
        $settings = get_option('wpe_editor_settings');
        $this->assertIsArray($settings);
        $this->assertArrayHasKey('start_category', $settings);
        $this->assertArrayHasKey('default_columns', $settings);
        $this->assertArrayHasKey('instructions', $settings);
    }
    
    /**
     * Test activation merges with existing options
     */
    public function test_activation_merges_with_existing() {
        // Set partial options
        $existing = [
            'start_category' => 'custom-category',
            'default_columns' => ['sku', 'regular_price'],
        ];
        update_option('wpe_editor_settings', $existing);
        
        // Trigger activation
        WPE_Plugin::activate();
        
        // Check option was merged
        $settings = get_option('wpe_editor_settings');
        $this->assertEquals('custom-category', $settings['start_category']);
        $this->assertArrayHasKey('instructions', $settings); // Should be added
        $this->assertNotEmpty($settings['instructions']);
    }
    
    /**
     * Test activation removes 'product' from default_columns if present
     */
    public function test_activation_removes_product_column() {
        // Set options with 'product' column (old behavior)
        $existing = [
            'start_category' => 'all',
            'default_columns' => ['product', 'sku', 'regular_price'],
            'instructions' => 'Test',
        ];
        update_option('wpe_editor_settings', $existing);
        
        // Trigger activation
        WPE_Plugin::activate();
        
        // Check 'product' was removed
        $settings = get_option('wpe_editor_settings');
        $this->assertNotContains('product', $settings['default_columns']);
        $this->assertContains('sku', $settings['default_columns']);
        $this->assertContains('regular_price', $settings['default_columns']);
    }
    
    /**
     * Test default columns don't include always-visible columns
     */
    public function test_default_columns_exclude_always_visible() {
        $defaults = WPE_Plugin::get_default_options();
        
        // 'product' (title) and 'actions' are always visible, should not be in defaults
        $this->assertNotContains('product', $defaults['default_columns']);
        $this->assertNotContains('actions', $defaults['default_columns']);
    }
    
    /**
     * Test all default columns are valid
     */
    public function test_default_columns_are_valid() {
        $defaults = WPE_Plugin::get_default_options();
        $valid_columns = [
            'sku',
            'status',
            'regular_price',
            'sale_price',
            'tax_status',
            'tax_class',
            'stock_status',
            'categories',
        ];
        
        foreach ($defaults['default_columns'] as $column) {
            $this->assertContains(
                $column,
                $valid_columns,
                "Column '{$column}' is not a valid column"
            );
        }
    }
    
    /**
     * Test options are serialized correctly
     */
    public function test_options_serialization() {
        $defaults = WPE_Plugin::get_default_options();
        update_option('wpe_editor_settings', $defaults);
        
        $retrieved = get_option('wpe_editor_settings');
        
        $this->assertEquals($defaults, $retrieved);
        $this->assertIsArray($retrieved['default_columns']);
        $this->assertIsString($retrieved['start_category']);
        $this->assertIsString($retrieved['instructions']);
    }
    
    /**
     * Test default instructions contain expected sections
     */
    public function test_default_instructions_content() {
        $defaults = WPE_Plugin::get_default_options();
        $instructions = $defaults['instructions'];
        
        // Check for key sections
        $this->assertStringContainsString('How to use:', $instructions);
        $this->assertStringContainsString('Tips:', $instructions);
        $this->assertStringContainsString('Price fields', $instructions);
        $this->assertStringContainsString('Title field', $instructions);
    }
}
