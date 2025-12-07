# Installation Guide

## Requirements

- **WordPress**: 6.0 or higher
- **PHP**: 7.4 or higher
- **WooCommerce**: Latest stable version (3.0+)
- **Required Capability**: `manage_woocommerce`

## Installation Steps

### Method 1: WordPress Admin Upload

1. Download the plugin ZIP file
2. Navigate to **WordPress Admin** → **Plugins** → **Add New**
3. Click **Upload Plugin** at the top of the page
4. Choose the downloaded ZIP file
5. Click **Install Now**
6. Once installed, click **Activate Plugin**

### Method 2: Manual Installation via FTP

1. Extract the plugin ZIP file
2. Connect to your server via FTP
3. Upload the `woo-price-editor` directory to `/wp-content/plugins/`
4. Navigate to **WordPress Admin** → **Plugins**
5. Find "Woo Price Editor" and click **Activate**

### Method 3: WP-CLI Installation

```bash
# Upload plugin to plugins directory
wp plugin activate woo-price-editor

# Or install from local ZIP file
wp plugin install /path/to/woo-price-editor.zip --activate
```

## Post-Installation

### Accessing the Plugin

Once activated, the plugin adds a menu item to the WordPress admin:

- **Location**: WordPress Admin → **Price Editor**
- **Settings**: WordPress Admin → **Price Editor** → **Settings**

### Initial Configuration

1. Navigate to **Price Editor** → **Settings**
2. Configure the following options:
   - **Start Category**: Select the default product category to display on load
   - **Default Columns**: Choose which columns are visible by default
   - **Instructions**: Customize the help text shown to users

3. Click **Save Changes**

### Verifying Installation

To verify the plugin is working correctly:

1. Navigate to **Price Editor** from the admin menu
2. The full-screen editor interface should load
3. You should see your WooCommerce products in a table
4. Try editing a product field (e.g., a price) to confirm functionality

## Troubleshooting

### Plugin Menu Not Visible

**Cause**: User lacks required capability
**Solution**: Ensure the logged-in user has the `manage_woocommerce` capability (typically Shop Manager or Administrator roles)

### Editor Page is Blank

**Cause**: Theme or plugin conflict, or missing template file
**Solution**: 
- Check for JavaScript errors in browser console
- Temporarily disable other plugins to identify conflicts
- Verify the file `woo-price-editor/templates/editor-shell.php` exists

### Products Not Loading

**Cause**: WooCommerce not activated or database issues
**Solution**:
- Ensure WooCommerce is active
- Verify products exist in WooCommerce
- Check browser console and server error logs for details

### Permission Errors

**Cause**: User lacks proper capability
**Solution**: 
- Verify user has `manage_woocommerce` capability
- Check with administrator if role permissions need adjustment

## Uninstallation

The plugin includes an uninstall routine that removes its data:

1. Navigate to **Plugins** page in WordPress Admin
2. **Deactivate** the plugin first
3. Click **Delete** on the deactivated plugin
4. Confirm deletion

This will remove:
- Plugin option `wpe_editor_settings` from the database
- All plugin files from the server

**Note**: Product data is not affected during uninstallation.
