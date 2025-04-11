<?php
/**
 * Settings page controller
 */

// Check if user is admin for editing
$can_edit = is_admin();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_edit) {
    $settings = $_POST['settings'] ?? [];
    
    if (!empty($settings)) {
        $conn = db_connect();
        
        foreach ($settings as $name => $value) {
            // Clean name for security
            $name = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
            
            // Update setting
            $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE name = ?");
            $stmt->bind_param('ss', $value, $name);
            $stmt->execute();
        }
        
        echo '<div class="alert alert-success">Settings updated successfully.</div>';
    }
}

// Get all settings
$settings = db_select("SELECT * FROM settings ORDER BY name");

// Group settings by category
$grouped_settings = [];
foreach ($settings as $setting) {
    $category = $setting['category'] ?? 'General';
    if (!isset($grouped_settings[$category])) {
        $grouped_settings[$category] = [];
    }
    $grouped_settings[$category][] = $setting;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Settings</h1>
</div>

<form method="POST" action="">
    <?php foreach ($grouped_settings as $category => $cat_settings): ?>
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-cog"></i> <?php echo htmlspecialchars($category); ?> Settings
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width: 30%">Setting</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cat_settings as $setting): ?>
                                <tr>
                                    <td>
                                        <label for="setting_<?php echo htmlspecialchars($setting['name']); ?>">
                                            <strong><?php echo ucwords(str_replace('_', ' ', $setting['name'])); ?></strong>
                                        </label>
                                        <?php if (!empty($setting['description'])): ?>
                                            <div class="small text-muted"><?php echo htmlspecialchars($setting['description']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($can_edit): ?>
                                            <?php if (isset($setting['type']) && $setting['type'] === 'textarea'): ?>
                                                <textarea class="form-control" id="setting_<?php echo htmlspecialchars($setting['name']); ?>" 
                                                       name="settings[<?php echo htmlspecialchars($setting['name']); ?>]" 
                                                       rows="3"><?php echo htmlspecialchars($setting['value']); ?></textarea>
                                            <?php elseif (isset($setting['type']) && $setting['type'] === 'boolean'): ?>
                                                <select class="form-select" id="setting_<?php echo htmlspecialchars($setting['name']); ?>" 
                                                       name="settings[<?php echo htmlspecialchars($setting['name']); ?>]">
                                                    <option value="1" <?php echo $setting['value'] == '1' ? 'selected' : ''; ?>>Yes</option>
                                                    <option value="0" <?php echo $setting['value'] == '0' ? 'selected' : ''; ?>>No</option>
                                                </select>
                                            <?php else: ?>
                                                <input type="text" class="form-control" id="setting_<?php echo htmlspecialchars($setting['name']); ?>" 
                                                       name="settings[<?php echo htmlspecialchars($setting['name']); ?>]" 
                                                       value="<?php echo htmlspecialchars($setting['value']); ?>">
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if ($setting['type'] === 'boolean'): ?>
                                                <?php echo $setting['value'] == '1' ? 'Yes' : 'No'; ?>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($setting['value']); ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <?php if ($can_edit): ?>
        <div class="d-flex justify-content-end mb-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Settings
            </button>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> You need administrator privileges to modify settings.
        </div>
    <?php endif; ?>
</form>