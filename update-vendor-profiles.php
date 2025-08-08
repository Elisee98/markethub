<?php
/**
 * Update Vendor Profiles
 * Add logos, addresses, and other information to vendor stores
 */

require_once 'config/config.php';

echo "<h1>🏪 Updating Vendor Profiles</h1>";

// Sample vendor profile updates
$vendor_updates = [
    [
        'username' => 'techstore_rw',
        'logo_url' => 'assets/images/logos/techstore-logo.png',
        'address' => 'KG 15 Ave, Nyarugenge District, Kigali, Rwanda',
        'business_hours' => "Monday - Friday: 8:00 AM - 6:00 PM\nSaturday: 9:00 AM - 5:00 PM\nSunday: Closed",
        'website' => 'https://techstore.rw',
        'phone' => '+250788123456',
        'email' => 'info@techstore.rw'
    ],
    [
        'username' => 'fashion_hub',
        'logo_url' => 'assets/images/logos/fashion-hub-logo.png',
        'address' => 'KN 3 Rd, Gasabo District, Kigali, Rwanda',
        'business_hours' => "Monday - Saturday: 9:00 AM - 7:00 PM\nSunday: 10:00 AM - 4:00 PM",
        'website' => 'https://fashionhub.rw',
        'phone' => '+250788234567',
        'email' => 'contact@fashionhub.rw'
    ],
    [
        'username' => 'shoe_palace',
        'logo_url' => 'assets/images/logos/shoe-palace-logo.png',
        'address' => 'KG 9 Ave, Kicukiro District, Kigali, Rwanda',
        'business_hours' => "Monday - Friday: 8:30 AM - 6:30 PM\nSaturday: 9:00 AM - 6:00 PM\nSunday: 11:00 AM - 3:00 PM",
        'website' => 'https://shoepalace.rw',
        'phone' => '+250788345678',
        'email' => 'sales@shoepalace.rw'
    ],
    [
        'username' => 'gadget_world',
        'logo_url' => 'assets/images/logos/gadget-world-logo.png',
        'address' => 'KG 11 Ave, Nyarugenge District, Kigali, Rwanda',
        'business_hours' => "Monday - Friday: 8:00 AM - 7:00 PM\nSaturday: 9:00 AM - 6:00 PM\nSunday: Closed",
        'website' => 'https://gadgetworld.rw',
        'phone' => '+250788456789',
        'email' => 'hello@gadgetworld.rw'
    ],
    [
        'username' => 'pixel_store',
        'logo_url' => 'assets/images/logos/pixel-store-logo.png',
        'address' => 'KG 7 Ave, Gasabo District, Kigali, Rwanda',
        'business_hours' => "Monday - Saturday: 9:00 AM - 6:00 PM\nSunday: 10:00 AM - 4:00 PM",
        'website' => 'https://pixelstore.rw',
        'phone' => '+250788567890',
        'email' => 'support@pixelstore.rw'
    ]
];

echo "<h2>Updating Vendor Store Information</h2>";

foreach ($vendor_updates as $update) {
    try {
        // Get vendor ID
        $vendor = $database->fetch("SELECT id FROM users WHERE username = ?", [$update['username']]);
        
        if ($vendor) {
            $vendor_id = $vendor['id'];
            
            // Update vendor store information
            $database->execute(
                "UPDATE vendor_stores SET 
                    logo_url = ?, 
                    address = ?, 
                    business_hours = ?, 
                    website = ?, 
                    phone = ?, 
                    email = ?,
                    updated_at = NOW()
                 WHERE vendor_id = ?",
                [
                    $update['logo_url'],
                    $update['address'],
                    $update['business_hours'],
                    $update['website'],
                    $update['phone'],
                    $update['email'],
                    $vendor_id
                ]
            );
            
            echo "✅ Updated profile for: {$update['username']}<br>";
        } else {
            echo "❌ Vendor not found: {$update['username']}<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error updating {$update['username']}: " . $e->getMessage() . "<br>";
    }
}

echo "<h2>Creating Logo Placeholder Directory</h2>";

// Create logos directory if it doesn't exist
$logos_dir = 'assets/images/logos';
if (!is_dir($logos_dir)) {
    if (mkdir($logos_dir, 0755, true)) {
        echo "✅ Created logos directory: {$logos_dir}<br>";
    } else {
        echo "❌ Failed to create logos directory<br>";
    }
} else {
    echo "ℹ️ Logos directory already exists<br>";
}

echo "<h2>Creating Simple Logo Placeholders</h2>";

// Create simple SVG logo placeholders for each vendor
$logo_templates = [
    'techstore-logo.png' => [
        'name' => 'TechStore',
        'color' => '#3b82f6',
        'icon' => '💻'
    ],
    'fashion-hub-logo.png' => [
        'name' => 'Fashion Hub',
        'color' => '#ec4899',
        'icon' => '👗'
    ],
    'shoe-palace-logo.png' => [
        'name' => 'Shoe Palace',
        'color' => '#f59e0b',
        'icon' => '👟'
    ],
    'gadget-world-logo.png' => [
        'name' => 'Gadget World',
        'color' => '#10b981',
        'icon' => '📱'
    ],
    'pixel-store-logo.png' => [
        'name' => 'Pixel Store',
        'color' => '#8b5cf6',
        'icon' => '📱'
    ]
];

foreach ($logo_templates as $filename => $template) {
    $logo_path = $logos_dir . '/' . $filename;
    
    // Create a simple SVG logo
    $svg_content = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="120" height="120" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
    <circle cx="60" cy="60" r="60" fill="' . $template['color'] . '"/>
    <text x="60" y="45" text-anchor="middle" fill="white" font-family="Arial, sans-serif" font-size="12" font-weight="bold">' . htmlspecialchars($template['name']) . '</text>
    <text x="60" y="75" text-anchor="middle" font-size="24">' . $template['icon'] . '</text>
</svg>';
    
    // Convert filename to SVG for now (you can replace with actual PNG files later)
    $svg_filename = str_replace('.png', '.svg', $filename);
    $svg_path = $logos_dir . '/' . $svg_filename;
    
    if (file_put_contents($svg_path, $svg_content)) {
        echo "✅ Created logo: {$svg_filename}<br>";
        
        // Update the database to use SVG instead of PNG
        $database->execute(
            "UPDATE vendor_stores vs 
             JOIN users u ON vs.vendor_id = u.id 
             SET vs.logo_url = ? 
             WHERE vs.logo_url = ?",
            ['assets/images/logos/' . $svg_filename, 'assets/images/logos/' . $filename]
        );
    } else {
        echo "❌ Failed to create logo: {$svg_filename}<br>";
    }
}

echo "<h2>📊 Updated Vendor Profiles</h2>";

// Show updated vendor information
$updated_vendors = $database->fetchAll(
    "SELECT u.username, vs.store_name, vs.logo_url, vs.address, vs.website, vs.phone 
     FROM users u 
     JOIN vendor_stores vs ON u.id = vs.vendor_id 
     WHERE u.user_type = 'vendor' AND u.status = 'active'
     ORDER BY vs.store_name"
);

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;'>";
foreach ($updated_vendors as $vendor) {
    echo "<div style='background: white; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
    
    if ($vendor['logo_url']) {
        echo "<img src='{$vendor['logo_url']}' style='width: 60px; height: 60px; border-radius: 50%; margin-bottom: 15px;' onerror='this.style.display=\"none\"'>";
    }
    
    echo "<h4 style='margin: 0 0 10px 0; color: #374151;'>{$vendor['store_name']}</h4>";
    echo "<p style='margin: 0 0 5px 0; color: #6b7280; font-size: 14px;'>@{$vendor['username']}</p>";
    
    if ($vendor['address']) {
        echo "<p style='margin: 0 0 5px 0; color: #6b7280; font-size: 14px;'><i class='fas fa-map-marker-alt'></i> " . substr($vendor['address'], 0, 50) . "...</p>";
    }
    
    if ($vendor['phone']) {
        echo "<p style='margin: 0 0 5px 0; color: #6b7280; font-size: 14px;'><i class='fas fa-phone'></i> {$vendor['phone']}</p>";
    }
    
    if ($vendor['website']) {
        echo "<p style='margin: 0; color: #10b981; font-size: 14px;'><i class='fas fa-globe'></i> <a href='{$vendor['website']}' target='_blank' style='color: #10b981;'>{$vendor['website']}</a></p>";
    }
    
    echo "</div>";
}
echo "</div>";

echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 8px; margin: 30px 0;'>";
echo "<h3 style='color: #155724; margin: 0 0 10px 0;'>✅ Vendor Profiles Updated!</h3>";
echo "<p style='color: #155724; margin: 0;'>All vendor stores now have complete profiles with logos, addresses, business hours, and contact information.</p>";
echo "<p style='margin: 10px 0 0 0;'><a href='vendors.php' style='color: #155724; font-weight: bold;'>View Updated Vendors →</a></p>";
echo "</div>";

?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; max-width: 1000px; margin: 0 auto; }
h1 { color: #10b981; text-align: center; }
h2 { color: #374151; border-bottom: 2px solid #e5e7eb; padding-bottom: 5px; margin-top: 30px; }
</style>
