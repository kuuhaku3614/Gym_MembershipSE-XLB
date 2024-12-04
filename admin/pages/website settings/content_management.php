<?php
require_once 'config.php';

// Function to handle general content updates
function updateWebsiteContent($section, $data) {
    global $pdo;
    
    try {
        switch ($section) {
            case 'welcome':
                $stmt = $pdo->prepare("UPDATE website_content SET company_name = :name, description = :desc WHERE section = 'welcome'");
                $stmt->execute([
                    ':name' => $data['company_name'],
                    ':desc' => $data['welcome_description']
                ]);
                break;
            
            case 'offers':
                $stmt = $pdo->prepare("UPDATE website_content SET description = :desc WHERE section = 'offers'");
                $stmt->execute([':desc' => $data['offers_description']]);
                break;
            
            case 'about_us':
                $stmt = $pdo->prepare("UPDATE website_content SET description = :desc WHERE section = 'about_us'");
                $stmt->execute([':desc' => $data['about_description']]);
                break;
            
            case 'contact':
                $stmt = $pdo->prepare("UPDATE website_content SET location = :loc, phone = :phone, email = :email WHERE section = 'contact'");
                $stmt->execute([
                    ':loc' => $data['location'],
                    ':phone' => $data['phone'],
                    ':email' => $data['email']
                ]);
                break;
            
            default:
                throw new Exception("Invalid section for update");
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Content Update Error: " . $e->getMessage());
        return false;
    }
}

// Function to handle item additions (offers, products, staff, gallery)
function addItem($type, $data, $imagePath) {
    global $pdo;
    
    try {
        switch ($type) {
            case 'gym_offer':
                $stmt = $pdo->prepare("INSERT INTO gym_offers (title, description, image_path) VALUES (:title, :desc, :img)");
                $stmt->execute([
                    ':title' => $data['title'],
                    ':desc' => $data['description'],
                    ':img' => $imagePath,
                ]);
                break;
            
            case 'product':
                $stmt = $pdo->prepare("INSERT INTO products (name, description, image_path) VALUES (:name, :desc, :img)");
                $stmt->execute([
                    ':name' => $data['name'],
                    ':desc' => $data['description'],
                    ':img' => $imagePath
                ]);
                break;
            
            case 'staff':
                $stmt = $pdo->prepare("INSERT INTO staff (name, status, image_path) VALUES (:name, :status, :img)");
                $stmt->execute([
                    ':name' => $data['name'],
                    ':status' => $data['status'],
                    ':img' => $imagePath
                ]);
                break;
            
            case 'gallery':
                $stmt = $pdo->prepare("INSERT INTO gallery_images (image_path, alt_text) VALUES (:img, :alt)");
                $stmt->execute([
                    ':img' => $imagePath,
                    ':alt' => $data['alt_text'] ?? 'Gallery Image'
                ]);
                break;
            
            default:
                throw new Exception("Invalid item type");
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Item Addition Error: " . $e->getMessage());
        return false;
    }
}

// Function to handle item deletion
function deleteItem($type, $itemId) {
    global $pdo;
    
    try {
        // First, fetch the image path to delete the file
        switch ($type) {
            case 'gym_offer':
                $stmt = $pdo->prepare("SELECT image_path FROM gym_offers WHERE id = :id");
                break;
            
            case 'product':
                $stmt = $pdo->prepare("SELECT image_path FROM products WHERE id = :id");
                break;
            
            case 'staff':
                $stmt = $pdo->prepare("SELECT image_path FROM staff WHERE id = :id");
                break;
            
            case 'gallery':
                $stmt = $pdo->prepare("SELECT image_path FROM gallery_images WHERE id = :id");
                break;
            
            default:
                throw new Exception("Invalid item type");
        }
        
        $stmt->execute([':id' => $itemId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($item) {
            // Delete associated image file
            deleteImageFile($item['image_path']);
            
            // Delete database record
            switch ($type) {
                case 'gym_offer':
                    $stmt = $pdo->prepare("DELETE FROM gym_offers WHERE id = :id");
                    break;
                
                case 'product':
                    $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
                    break;
                
                case 'staff':
                    $stmt = $pdo->prepare("DELETE FROM staff WHERE id = :id");
                    break;
                
                case 'gallery':
                    $stmt = $pdo->prepare("DELETE FROM gallery_images WHERE id = :id");
                    break;
            }
            
            $stmt->execute([':id' => $itemId]);
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Item Deletion Error: " . $e->getMessage());
        return false;
    }
}
?>