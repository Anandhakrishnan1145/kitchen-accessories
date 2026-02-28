<?php
// Start the session
session_start();

// Include database connection
include '../config.php'; // Adjust path as needed

// Check if user is logged in (assuming user details are stored in session)
$user_logged_in = isset($_SESSION['user_id']);
$user_id = $user_logged_in ? $_SESSION['user_id'] : null;
$username = $user_logged_in ? $_SESSION['username'] : null;

// Process chatbot messages
if(isset($_POST['user_message']) && !empty($_POST['user_message'])) {
    $user_message = trim($_POST['user_message']);
    $response = generateChatbotResponse($user_message, $conn, $user_id);
    
    // Return response as JSON
    header('Content-Type: application/json');
    echo json_encode(['response' => $response]);
    exit;
}

/**
 * Generate chatbot response based on user message
 * @param string $message User message
 * @param mysqli $conn Database connection
 * @param int|null $user_id User ID if logged in
 * @return string Chatbot response
 */
function generateChatbotResponse($message, $conn, $user_id) {
    // Convert message to lowercase for easier matching
    $message = strtolower($message);
    
    // Check if message is kitchen-related
    if(isKitchenRelated($message)) {
        // Process kitchen-related queries
        
        // Store queries
        if(isStoreQuery($message)) {
            // Product catalog related queries
            if(containsAny($message, ['product', 'item', 'available', 'list', 'catalog', 'inventory'])) {
                if(containsAny($message, ['list', 'available', 'show', 'what', 'all'])) {
                    return getProductList($conn);
                }
                else if(preg_match('/(find|search|looking for|where|have|get) (.+)/', $message, $matches)) {
                    return searchProduct($conn, $matches[2]);
                }
                else {
                    return "I can help you find kitchen products! Try asking something like 'list products', 'search for pans', or 'I'm looking for cooking spoons'.";
                }
            }
            // Price related queries
            else if(containsAny($message, ['price', 'cost', 'how much', 'expensive', 'cheap'])) {
                // Extract product name from message
                $product = extractProductName($message);
                if($product) {
                    return getProductPrice($conn, $product);
                }
                return "What product would you like to know the price of? We have pans, utensils, containers, and more.";
            }
            // Order related queries
            else if(containsAny($message, ['order', 'purchase', 'buy', 'delivery', 'shipping'])) {
                if($user_id) {
                    return getUserOrders($conn, $user_id);
                } else {
                    return "Please log in to check your orders. I can help you track deliveries, view past purchases, and check order status once you're logged in.";
                }
            }
            // Category related queries
            else if(containsAny($message, ['category', 'categories', 'types', 'sections'])) {
                return getCategories($conn);
            }
            // Material related queries
            else if(containsAny($message, ['material', 'made of', 'composition', 'what is it made of'])) {
                $material = extractMaterialName($message);
                if($material) {
                    return getProductsByMaterial($conn, $material);
                } else {
                    return getMaterials($conn);
                }
            }
            // Contact/support queries
            else if(containsAny($message, ['contact', 'support', 'help', 'service', 'complaint', 'feedback', 'talk to human'])) {
                return "You can contact our Kitchen Accessories Stores support team at wholesalekitchen@gmail.com or call 9876543210. Our team is available Monday to Saturday, 9 AM to 6 PM.";
            }
            else {
                return getGeneralStoreInfo();
            }
        }
        // Cooking advice queries
        else if(isCookingQuery($message)) {
            return getCookingAdvice($message);
        }
        // Appliance queries
        else if(isApplianceQuery($message)) {
            return getApplianceInfo($message);
        }
        // Utensil queries
        else if(isUtensilQuery($message)) {
            return getUtensilInfo($message);
        }
        // Kitchen organization queries
        else if(isOrganizationQuery($message)) {
            return getOrganizationTips($message);
        }
        // General kitchen queries
        else {
            return getGeneralKitchenInfo($message);
        }
    }
    // General conversation
    else if(containsAny($message, ['hello', 'hi', 'hey', 'greetings'])) {
        return getGreeting();
    }
    else if(containsAny($message, ['thank', 'thanks', 'appreciate'])) {
        return "You're welcome! I'm happy to help with any kitchen-related questions. Is there anything specific about Kitchen Accessories Stores or cooking you'd like to know?";
    }
    else if(containsAny($message, ['bye', 'goodbye', 'see you', 'talk later'])) {
        return "Thank you for chatting! Feel free to return whenever you have kitchen-related questions. Happy cooking!";
    }
    else {
        // Default response for non-kitchen topics
        return "I specialize in Kitchen Accessories Stores, cooking, and culinary topics. I'd be happy to help with questions about cookware, utensils, appliances, cooking techniques, or our store's products. What would you like to know about kitchen-related topics?";
    }
}

/**
 * Check if a message is kitchen-related
 * @param string $message User message
 * @return bool True if kitchen-related
 */
function isKitchenRelated($message) {
    $kitchen_terms = [
        'kitchen', 'cook', 'bake', 'recipe', 'food', 'meal', 'dish', 'utensil', 'appliance', 'cookware', 
        'pan', 'pot', 'knife', 'spoon', 'fork', 'plate', 'bowl', 'cup', 'glass', 'container', 'storage',
        'cutting', 'chopping', 'slice', 'dice', 'mince', 'grate', 'peel', 'blend', 'mix', 'whisk', 'stir',
        'fry', 'saute', 'boil', 'simmer', 'roast', 'bake', 'broil', 'grill', 'steam', 'microwave', 
        'refrigerator', 'fridge', 'oven', 'stove', 'cooktop', 'dishwasher', 'mixer', 'blender', 'processor',
        'spatula', 'ladle', 'tongs', 'strainer', 'colander', 'grater', 'peeler', 'cutter', 'board', 'appam',
        'idli', 'dosa', 'pressure cooker', 'copper', 'steel', 'wooden', 'plastic', 'silicone', 'ceramic',
        'non-stick', 'cast iron', 'stainless steel', 'aluminum', 'glass', 'porcelain', 'measuring', 'scale',
        'timer', 'thermometer', 'product', 'price', 'order', 'category', 'material'
    ];
    
    foreach($kitchen_terms as $term) {
        if(strpos($message, $term) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Check if a message contains any of the terms in the array
 * @param string $message User message
 * @param array $terms Terms to check for
 * @return bool True if message contains any term
 */
function containsAny($message, $terms) {
    foreach($terms as $term) {
        if(strpos($message, $term) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Check if a message is store-related
 * @param string $message User message
 * @return bool True if store-related
 */
function isStoreQuery($message) {
    $store_terms = [
        'store', 'shop', 'buy', 'purchase', 'order', 'price', 'cost', 'product', 'item', 'catalog',
        'inventory', 'stock', 'available', 'delivery', 'shipping', 'return', 'refund', 'warranty',
        'discount', 'sale', 'offer', 'deal', 'coupon', 'contact', 'support', 'help', 'service',
        'complaint', 'feedback', 'category', 'material', 'brand'
    ];
    
    foreach($store_terms as $term) {
        if(strpos($message, $term) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Check if a message is cooking-related
 * @param string $message User message
 * @return bool True if cooking-related
 */
function isCookingQuery($message) {
    $cooking_terms = [
        'cook', 'recipe', 'prepare', 'make', 'bake', 'fry', 'roast', 'grill', 'steam', 'boil',
        'simmer', 'saute', 'food', 'dish', 'meal', 'breakfast', 'lunch', 'dinner', 'snack',
        'dessert', 'ingredient', 'technique', 'method', 'how to', 'temperature', 'time',
        'cuisine', 'traditional', 'healthy', 'quick', 'easy', 'beginner', 'advanced',
        'vegetarian', 'vegan', 'gluten-free', 'dairy-free', 'low-carb', 'keto', 'paleo'
    ];
    
    foreach($cooking_terms as $term) {
        if(strpos($message, $term) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Check if a message is appliance-related
 * @param string $message User message
 * @return bool True if appliance-related
 */
function isApplianceQuery($message) {
    $appliance_terms = [
        'appliance', 'machine', 'blender', 'mixer', 'processor', 'grinder', 'cooker', 'oven',
        'microwave', 'toaster', 'kettle', 'coffee maker', 'juicer', 'fridge', 'refrigerator',
        'freezer', 'dishwasher', 'stove', 'range', 'cooktop', 'hood', 'exhaust', 'air fryer',
        'slow cooker', 'pressure cooker', 'instant pot', 'rice cooker', 'electric', 'gas'
    ];
    
    foreach($appliance_terms as $term) {
        if(strpos($message, $term) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Check if a message is utensil-related
 * @param string $message User message
 * @return bool True if utensil-related
 */
function isUtensilQuery($message) {
    $utensil_terms = [
        'utensil', 'tool', 'cookware', 'pan', 'pot', 'knife', 'spoon', 'fork', 'spatula',
        'ladle', 'tongs', 'whisk', 'strainer', 'colander', 'grater', 'peeler', 'cutter',
        'board', 'plate', 'bowl', 'cup', 'glass', 'container', 'storage', 'measuring',
        'scale', 'timer', 'thermometer', 'wok', 'appam', 'skillet', 'griddle', 'grill',
        'sheet', 'baking', 'roasting', 'cutting', 'chopping', 'rolling', 'pin', 'masher'
    ];
    
    foreach($utensil_terms as $term) {
        if(strpos($message, $term) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Check if a message is organization-related
 * @param string $message User message
 * @return bool True if organization-related
 */
function isOrganizationQuery($message) {
    $organization_terms = [
        'organize', 'organization', 'storage', 'store', 'keep', 'arrange', 'cabinet',
        'drawer', 'shelf', 'rack', 'hook', 'hanger', 'basket', 'bin', 'container',
        'box', 'jar', 'canister', 'set', 'stack', 'space', 'saving', 'compact',
        'efficient', 'clutter', 'clean', 'tidy', 'neat', 'mess', 'mess-free'
    ];
    
    foreach($organization_terms as $term) {
        if(strpos($message, $term) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Extract product name from message
 * @param string $message User message
 * @return string|null Product name or null
 */
function extractProductName($message) {
    $product_terms = [
        "pan", "pot", "knife", "spoon", "fork", "plate", "bowl", "cup", "glass", "container",
        "board", "grater", "peeler", "spatula", "ladle", "tongs", "whisk", "strainer", "colander",
        "measuring", "scale", "timer", "thermometer", "wok", "skillet", "griddle", "grill",
        "sheet", "baking", "roasting", "cutting", "chopping", "rolling", "pin", "masher", "appam",
        "idli", "dosa", "pressure cooker", "blender", "mixer", "processor", "grinder", "toaster",
        "kettle", "coffee maker", "juicer", "air fryer", "slow cooker", "rice cooker"
    ];
    
    foreach($product_terms as $term) {
        if(strpos($message, $term) !== false) {
            return $term;
        }
    }
    
    return null;
}

/**
 * Extract material name from message
 * @param string $message User message
 * @return string|null Material name or null
 */
function extractMaterialName($message) {
    $material_terms = [
        "copper", "steel", "stainless steel", "cast iron", "iron", "aluminum", "wooden",
        "wood", "plastic", "silicone", "ceramic", "glass", "porcelain", "non-stick",
        "clay", "stone", "marble", "granite"
    ];
    
    foreach($material_terms as $term) {
        if(strpos($message, $term) !== false) {
            return $term;
        }
    }
    
    return null;
}

/**
 * Get a random greeting message
 * @return string Greeting message
 */
function getGreeting() {
    $greetings = [
        "Hello! I'm your kitchen assistant. I can help with questions about Kitchen Accessories Stores, cooking techniques, and our store's products. What can I help you with today?",
        "Hi there! Welcome to our kitchen store's AI assistant. I'm here to help with all your kitchen-related questions. What would you like to know?",
        "Hey! I'm your culinary companion. Whether you need product recommendations, cooking advice, or information about our store, I'm here to assist. How can I help you today?",
        "Greetings! I'm your kitchen helper AI. I'm knowledgeable about cookware, utensils, appliances, and cooking techniques. What kitchen topic can I help you with?"
    ];
    return $greetings[array_rand($greetings)];
}

/**
 * Get a list of products
 * @param mysqli $conn Database connection
 * @return string Product list message
 */
function getProductList($conn) {
    $query = "SELECT product_name, selling_price, material FROM products WHERE is_visible = 1 LIMIT 10";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) > 0) {
        $response = "Here are some of our popular kitchen products:\n\n";
        while($row = mysqli_fetch_assoc($result)) {
            $response .= "• " . $row['product_name'] . " - ₹" . $row['selling_price'] . " - " . $row['material'] . "\n";
        }
        $response .= "\nWe have many more items in stock! Would you like to search for specific products or browse by category?";
        return $response;
    } else {
        return "I'm having trouble accessing our product catalog at the moment. Could you please try asking about specific kitchen items like pans, utensils, or storage containers?";
    }
}

/**
 * Search for a specific product
 * @param mysqli $conn Database connection
 * @param string $term Search term
 * @return string Search results
 */
function searchProduct($conn, $term) {
    $term = mysqli_real_escape_string($conn, $term);
    $query = "SELECT product_name, selling_price, material FROM products 
              WHERE product_name LIKE '%$term%' AND is_visible = 1 
              LIMIT 5";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) > 0) {
        $response = "I found these kitchen products matching '" . $term . "':\n\n";
        while($row = mysqli_fetch_assoc($result)) {
            $response .= "• " . $row['product_name'] . " - ₹" . $row['selling_price'] . " - " . $row['material'] . "\n";
        }
        $response .= "\nWould you like more details about any of these items? Or would you like to see more products?";
        return $response;
    } else {
        // Provide alternatives or suggestions
        return "I couldn't find any products matching '" . $term . "' in our inventory. Here are some alternatives you might consider:\n\n• We have a variety of cooking utensils made from different materials\n• Our cookware collection includes pans, pots, and special items like appam makers\n• For food storage, we offer containers in various sizes and materials\n\nCould you tell me more about what you're looking for?";
    }
}

/**
 * Get price for a specific product
 * @param mysqli $conn Database connection
 * @param string $term Product term
 * @return string Price information
 */
function getProductPrice($conn, $term) {
    $term = mysqli_real_escape_string($conn, $term);
    $query = "SELECT product_name, selling_price, material FROM products 
              WHERE product_name LIKE '%$term%' AND is_visible = 1 
              LIMIT 5";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) > 0) {
        $response = "Here are the prices for " . $term . " products in our store:\n\n";
        while($row = mysqli_fetch_assoc($result)) {
            $response .= "• " . $row['product_name'] . " - ₹" . $row['selling_price'] . " - " . $row['material'] . "\n";
        }
        $response .= "\nOur prices include GST and we offer free shipping on orders above ₹1000. Would you like to know about any specific " . $term . " options?";
        return $response;
    } else {
        return "I couldn't find any " . $term . " products in our current inventory. We regularly update our stock. Would you like me to suggest some alternatives or check other kitchen products?";
    }
}

/**
 * Get user orders
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return string Order information
 */
function getUserOrders($conn, $user_id) {
    $user_id = mysqli_real_escape_string($conn, $user_id);
    $query = "SELECT order_id, total_amount, created_at, order_confirmation, delivery_confirmation 
              FROM esales 
              WHERE user_id = $user_id 
              ORDER BY created_at DESC 
              LIMIT 5";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) > 0) {
        $response = "Here are your recent kitchen accessory orders:\n\n";
        while($row = mysqli_fetch_assoc($result)) {
            $date = date('M j, Y', strtotime($row['created_at']));
            $status = ($row['delivery_confirmation'] == 'confirmed') ? "Delivered" : 
                     (($row['order_confirmation'] == 'confirmed') ? "Confirmed" : "Processing");
            
            $response .= "• Order #" . $row['order_id'] . " - ₹" . $row['total_amount'] . " - " . $date . " - Status: " . $status . "\n";
        }
        $response .= "\nFor order details, you can click on the order ID in your account. Would you like to check the status of a specific order or place a new one?";
        return $response;
    } else {
        return "You don't have any orders with us yet. Our kitchen store offers a wide range of high-quality products for your culinary needs. Would you like to browse our bestsellers or search for specific kitchen items?";
    }
}

/**
 * Get product categories
 * @param mysqli $conn Database connection
 * @return string Categories information
 */
function getCategories($conn) {
    $query = "SELECT category_name FROM categories LIMIT 10";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) > 0) {
        $response = "We organize our kitchen products into these categories:\n\n";
        while($row = mysqli_fetch_assoc($result)) {
            $response .= "• " . $row['category_name'] . "\n";
        }
        $response .= "\nEach category offers a variety of products to enhance your cooking experience. Which category would you like to explore?";
        return $response;
    } else {
        return "Our kitchen store offers products in various categories including:\n\n• Cookware (pans, pots, woks)\n• Utensils & Tools\n• Storage & Organization\n• Bakeware\n• Appliances\n• Serveware\n• Specialty Items\n\nWhich category interests you the most?";
    }
}

/**
 * Get available materials
 * @param mysqli $conn Database connection
 * @return string Materials information
 */
function getMaterials($conn) {
    $query = "SELECT DISTINCT material FROM products WHERE material != '' LIMIT 10";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) > 0) {
        $response = "Our kitchen products are available in these materials:\n\n";
        while($row = mysqli_fetch_assoc($result)) {
            $response .= "• " . $row['material'] . "\n";
        }
        $response .= "\nEach material has its own benefits for different cooking needs. For example, copper provides excellent heat conductivity, while stainless steel is durable and easy to clean. Which material would you like to learn more about?";
        return $response;
    } else {
        return "We offer kitchen products in various materials including:\n\n• Stainless Steel - Durable and easy to clean\n• Copper - Excellent heat conductivity\n• Cast Iron - Great heat retention and adds iron to food\n• Non-stick - Easy cooking and cleaning\n• Silicone - Heat-resistant and flexible\n• Wood - Natural and gentle on cookware\n• Ceramic - Non-reactive and even heating\n\nWhich material are you most interested in?";
    }
}

/**
 * Get products by material
 * @param mysqli $conn Database connection
 * @param string $material Material name
 * @return string Product information
 */
function getProductsByMaterial($conn, $material) {
    $material = mysqli_real_escape_string($conn, $material);
    $query = "SELECT product_name, selling_price FROM products 
              WHERE material LIKE '%$material%' AND is_visible = 1 
              LIMIT 5";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) > 0) {
        $response = "Here are our kitchen products made of " . $material . ":\n\n";
        while($row = mysqli_fetch_assoc($result)) {
            $response .= "• " . $row['product_name'] . " - ₹" . $row['selling_price'] . "\n";
        }
        
        // Add educational content about the material
        $response .= "\n" . getMaterialInfo($material);
        
        return $response;
    } else {
        return "I couldn't find any " . $material . " products in our current inventory. " . getMaterialInfo($material) . "\n\nWould you like to explore other materials or product categories?";
    }
}

/**
 * Get information about a specific material
 * @param string $material Material name
 * @return string Material information
 */
function getMaterialInfo($material) {
    $material_info = [
        "copper" => "Copper cookware is known for its excellent heat conductivity and even heating. It's responsive to temperature changes, making it ideal for delicate cooking tasks. Copper requires regular polishing to maintain its shine and is often lined with stainless steel for safety.",
        "steel" => "Steel cookware is durable, affordable, and versatile. It heats quickly and is suitable for high-temperature cooking. Steel is dishwasher safe and works on all cooktops, including induction.",
        "stainless steel" => "Stainless steel is prized for its durability, resistance to corrosion, and ease of maintenance. It's non-reactive, dishwasher safe, and compatible with all cooktops. High-quality stainless steel often features an aluminum or copper core for better heat distribution.",
        "cast iron" => "Cast iron offers exceptional heat retention and durability. It can last for generations when properly maintained. Cast iron cookware is naturally non-stick when seasoned correctly and adds small amounts of dietary iron to food.",
        "iron" => "Iron cookware is known for its excellent heat retention and durability. It develops a natural non-stick surface when properly seasoned and can add beneficial dietary iron to your food.",
        "aluminum" => "Aluminum cookware is lightweight and conducts heat efficiently. It's often anodized or coated for durability and to prevent reactions with acidic foods.",
        "wooden" => "Wooden kitchen tools are gentle on cookware, preventing scratches on non-stick surfaces. They're non-reactive, don't conduct heat, and have natural antibacterial properties. With proper care, wooden utensils can last for years.",
        "wood" => "Wood is a natural material that's gentle on cookware and doesn't scratch non-stick surfaces. It's non-reactive, doesn't conduct heat, and has natural antibacterial properties. Wooden utensils require hand washing and occasional oiling to maintain their condition.",
        "plastic" => "Plastic kitchen tools are lightweight, affordable, and come in various colors. They're gentle on non-stick surfaces but should be heat-resistant for cooking. Look for BPA-free options for food safety.",
        "silicone" => "Silicone kitchen tools are heat-resistant up to 600°F, flexible, and non-stick. They don't scratch cookware, are stain-resistant, and dishwasher safe. Silicone is a great choice for spatulas, baking mats, and oven mitts.",
        "ceramic" => "Ceramic cookware offers non-stick performance without PFOA or PTFE chemicals. It provides even heating, is oven-safe, and comes in various colors and designs. Ceramic requires careful handling to prevent chipping.",
        "glass" => "Glass cookware is non-reactive, doesn't absorb flavors or odors, and allows you to monitor food while cooking. It's oven, microwave, and dishwasher safe but requires careful handling to prevent thermal shock.",
        "porcelain" => "Porcelain offers elegance and durability. It's non-reactive, dishwasher safe, and suitable for serving and baking. Porcelain retains heat well and is available in various decorative styles.",
        "non-stick" => "Non-stick cookware allows for cooking with minimal oil and easy cleanup. It's ideal for delicate foods like eggs and fish. For longevity, use wooden or silicone utensils and avoid high heat.",
        "clay" => "Clay cookware distributes heat evenly and retains moisture, enhancing flavor. It's ideal for slow cooking and keeps food warm longer. Clay requires special care and should be heated gradually.",
        "stone" => "Stone cookware has excellent heat retention and distribution. It's naturally non-stick when seasoned properly and adds a rustic touch to cooking. Stone requires careful handling and specific cleaning methods.",
        "marble" => "Marble is naturally cool, making it ideal for pastry work. It's non-reactive, elegant, and durable. Marble requires sealing to prevent stains and should be cleaned with non-acidic cleaners.",
        "granite" => "Granite cookware offers non-stick properties, durability, and even heat distribution. It's free from chemicals like PFOA and PTFE, scratch-resistant, and usually oven-safe. Granite requires proper care to maintain its surface."
    ];
    
    $material = strtolower($material);
    
    if(isset($material_info[$material])) {
        return $material_info[$material];
    } else {
        return "Different materials offer various benefits for kitchen products. Consider your cooking style, maintenance preferences, and budget when choosing.";
    }
}

/**
 * Get general store information
 * @return string Store information
 */
function getGeneralStoreInfo() {
    $store_info = [
        "Welcome to our Kitchen Accessories Stores ! We offer a wide range of high-quality kitchen products to enhance your cooking experience.",
        "Our store specializes in kitchen essentials including cookware, utensils, storage solutions, and specialty items. We curate products from trusted brands and artisans.",
        "We prioritize quality, durability, and functionality in our kitchen products. Our team tests and selects items that will make your cooking experience better.",
        "Our kitchen store offers free shipping on orders above ₹1000, a 30-day return policy, and expert customer service to help you find the perfect kitchen tools."
    ];
    
    return $store_info[array_rand($store_info)];
}

/**
 * Get cooking advice based on user query
 * @param string $message User message
 * @return string Cooking advice
 */
function getCookingAdvice($message) {
    // Basic cooking tips for common queries
    if(containsAny($message, ['beginner', 'start', 'basic', 'new', 'learning'])) {
        return "Here are some basic cooking tips for beginners:\n\n• Read recipes thoroughly before starting\n• Prep ingredients before cooking (mise en place)\n• Invest in a good chef's knife and cutting board\n• Start with simple recipes to build confidence\n• Taste as you cook and adjust seasonings\n• Don't be afraid to make mistakes - they're learning opportunities\n\nWould you like recommendations for beginner-friendly kitchen tools?";
    }
    else if(containsAny($message, ['rice', 'cook rice', 'perfect rice'])) {
        return "Tips for cooking perfect rice:\n\n• Rinse rice thoroughly before cooking to remove excess starch\n• Use the right water-to-rice ratio (typically 1:2 for white rice, 1:2.5 for brown rice)\n• Let rice rest 5-10 minutes after cooking\n• For fluffy rice, use a fork to gently fluff instead of stirring\n• A good rice cooker makes the process easier and more consistent\n\nWould you like to know about our rice cookers or other rice cooking accessories?";
    }
    else if(containsAny($message, ['spice', 'flavor', 'seasoning'])) {
        return "Tips for using spices and seasonings:\n\n• Whole spices last longer than ground spices\n• Toast whole spices before grinding for more flavor\n• Add dried herbs early in cooking, fresh herbs toward the end\n• Salt enhances flavors - add gradually and taste as you go\n• Store spices in airtight containers away from heat and light\n\nWe offer a variety of spice containers and grinders in our store. Would you like to know more?";
    }
    else if(containsAny($message, ['healthy', 'nutrition', 'diet'])) {
        return "Tips for healthier cooking:\n\n• Use olive oil or avocado oil instead of butter\n• Steam or roast vegetables instead of frying\n• Use herbs and spices instead of salt for flavor\n• Incorporate more plant-based proteins\n• Invest in tools like air fryers or steamers\n\nWould you like recommendations for kitchen tools that make healthy cooking easier?";
    }
    else if(containsAny($message, ['quick', 'fast', 'easy', 'simple'])) {
        return "Tips for quick and easy cooking:\n\n• Prep ingredients in advance when possible\n• Master one-pot or sheet pan meals\n• Use time-saving tools like pressure cookers or food processors\n• Keep a well-stocked pantry with versatile ingredients\n• Learn to repurpose leftovers creatively\n\nWould you like suggestions for kitchen tools that save time?";
    }
    else {
        return "Cooking is an art that improves with practice. For better results, use quality ingredients and the right tools for each task. Our store offers a wide range of Kitchen Accessories Stores to enhance your cooking experience. Is there a specific cooking technique or dish you'd like advice on?";
    }
}

/**
 * Get appliance information based on user query
 * @param string $message User message
 * @return string Appliance information
 */
function getApplianceInfo($message) {
    if(containsAny($message, ['pressure cooker', 'instant pot'])) {
        return "Pressure cookers significantly reduce cooking time by increasing the pressure inside the sealed pot. They're great for beans, tenderizing tough cuts of meat, and making quick soups and stews. Modern pressure cookers have multiple safety features and often include various cooking modes. When choosing one, consider size, material (stainless steel is durable), and additional features like delayed start or keep warm function.";
    }
    else if(containsAny($message, ['blender', 'mixer', 'food processor'])) {
        return "Blenders, mixers, and food processors each serve different purposes in the kitchen. Blenders are ideal for smooth liquids like smoothies and soups. Stand mixers excel at dough kneading and baking tasks. Food processors are versatile for chopping, slicing, and making pastes. Consider what you cook most often when choosing between these appliances, or invest in a multi-purpose appliance if you have limited space.";
    }
    else if(containsAny($message, ['microwave', 'oven'])) {
        return "Microwaves and ovens offer different cooking methods. Microwaves heat food quickly using radiation, making them ideal for reheating and defrosting. Conventional ovens provide more even cooking through surrounding heat, perfect for baking and roasting. Modern convection ovens circulate hot air for faster, more even results. Consider your cooking style and kitchen space when choosing between these appliances.";
    }
    else if(containsAny($message, ['air fryer'])) {
        return "Air fryers use rapid air circulation to create crispy food with little to no oil. They're excellent for healthier versions of fried foods like french fries, chicken wings, and vegetables. When choosing an air fryer, consider capacity, temperature range, and ease of cleaning. Most air fryers have dishwasher-safe parts for convenient cleanup.";
    }
    else if(containsAny($message, ['slow cooker', 'crock pot'])) {
        return "Slow cookers allow for hands-off cooking over several hours, making them perfect for busy schedules. They're ideal for stews, soups, and tenderizing tough cuts of meat. The low, slow cooking process enhances flavors and tenderizes food. Look for models with programmable timers, keep-warm functions, and removable ceramic inserts for easy cleaning.";
    }
    else {
        return "Kitchen appliances can greatly enhance your cooking efficiency. When choosing appliances, consider your cooking habits, kitchen space, and budget. Quality appliances are an investment that can last for years with proper care. Our store offers a curated selection of reliable kitchen appliances. Which specific appliance are you interested in learning more about?";
    }
}

/**
 * Get utensil information based on user query
 * @param string $message User message
 * @return string Utensil information
 */
function getUtensilInfo($message) {
    if(containsAny($message, ['knife', 'knives', 'cutting'])) {
        return "A good set of knives is essential for any kitchen. At minimum, you need a chef's knife (8-inch is versatile), a paring knife for small tasks, and a serrated knife for bread. Quality knives are an investment that will last years with proper care. Look for full-tang construction (blade extends through the handle) for durability. Store knives in a block, on a magnetic strip, or with blade guards to maintain sharpness and safety.";
    }
    else if(containsAny($message, ['pan', 'skillet', 'frying'])) {
        return "A quality frying pan or skillet is a kitchen essential. For versatility, consider a 10-12 inch stainless steel pan for most cooking tasks and a non-stick pan for delicate foods like eggs. Cast iron skillets offer excellent heat retention and develop a natural non-stick surface over time. When choosing, look for solid construction, comfortable handles, and compatibility with your cooktop.";
    }
    else if(containsAny($message, ['pot', 'saucepan'])) {
        return "Every kitchen needs a few good pots. A set typically includes a small saucepan (1-2 qt), medium saucepan (3-4 qt), and large pot (6-8 qt). Stainless steel with an aluminum or copper core offers excellent heat distribution. Look for tight-fitting lids, sturdy handles, and a thick, heavy bottom to prevent scorching. Consider your cooking habits when choosing sizes.";
    }
    else if(containsAny($message, ['spatula', 'turner', 'flipper'])) {
        return "Spatulas come in various materials and designs for different tasks. Silicone spatulas are heat-resistant and won't scratch non-stick surfaces. Metal spatulas work well for getting under foods on stainless steel or cast iron. Fish spatulas have a thin, flexible edge ideal for delicate foods. For baking, offset spatulas help with frosting cakes and transferring cookies.";
    }
    else if(containsAny($message, ['measuring', 'cups', 'spoons'])) {
        return "Accurate measuring tools are crucial for consistent cooking and baking results. A set of measuring cups for dry ingredients, a glass measuring cup for liquids, and measuring spoons are kitchen essentials. Look for durable materials like stainless steel or high-quality plastic, clear markings, and easy-to-clean designs. For precision baking, consider investing in a kitchen scale.";
    }
    else {
        return "Quality utensils make cooking more enjoyable and efficient. When selecting kitchen tools, consider durability, comfort, and compatibility with your cookware. Our store offers a wide range of utensils made from various materials to suit different cooking styles and preferences. Which specific utensil would you like to learn more about?";
    }
}

/**
 * Get organization tips based on user query
 * @param string $message User message
 * @return string Organization tips
 */
function getOrganizationTips($message) {
    if(containsAny($message, ['small', 'tiny', 'apartment', 'limited'])) {
        return "Tips for organizing a small kitchen:\n\n• Use vertical space with wall-mounted racks and shelves\n• Opt for stackable containers and nesting bowls\n• Consider magnetic knife strips or utensil holders\n• Use the inside of cabinet doors for additional storage\n• Choose multi-functional tools over single-purpose items\n• Regularly declutter and keep only essential items\n\nOur store offers space-saving solutions specifically designed for compact kitchens. Would you like some recommendations?";
    }
    else if(containsAny($message, ['pantry', 'cabinet', 'cupboard'])) {
        return "Tips for organizing pantries and cabinets:\n\n• Group similar items together (baking supplies, breakfast items, etc.)\n• Use clear containers for bulk items like flour, sugar, and grains\n• Label everything, especially if transferring to different containers\n• Place frequently used items at eye level\n• Use shelf risers to maximize vertical space\n• Consider pull-out drawers for deep cabinets\n\nWe offer a variety of pantry organization solutions. Which area would you like to focus on?";
    }
    else if(containsAny($message, ['drawer', 'utensil'])) {
        return "Tips for organizing kitchen drawers:\n\n• Use drawer dividers or organizers to prevent items from shifting\n• Store utensils with handles facing the same direction\n• Group similar items together (measuring tools, spatulas, etc.)\n• Consider vertical utensil organizers to save space\n• Use drawer liners to prevent sliding and protect surfaces\n• Regularly declutter and remove items you rarely use\n\nOur store offers various drawer organization solutions. Would you like to know more about specific options?";
    }
    else if(containsAny($message, ['spice', 'herb'])) {
        return "Tips for organizing spices and herbs:\n\n• Store in airtight containers away from heat and direct sunlight\n• Use uniform containers for a clean, organized look\n• Consider alphabetical arrangement or grouping by cuisine\n• Label with names and purchase/expiration dates\n• Use tiered shelves or door-mounted racks for visibility\n• Regularly review and replace old spices for best flavor\n\nWe offer various spice organization solutions. Would you like to explore our spice rack options?";
    }
    else if(containsAny($message, ['refrigerator', 'fridge'])) {
        return "Tips for organizing your refrigerator:\n\n• Store dairy in the coldest part (usually the middle)\n• Keep fruits and vegetables in their designated crisper drawers\n• Use clear containers for leftovers and label with dates\n• Store raw meat on the bottom shelf to prevent cross-contamination\n• Use bins or organizers to group similar items\n• Regularly clean out expired items\n\nWe offer refrigerator organization solutions like egg holders, produce savers, and stackable containers. Would you like more information?";
    }
    else {
        return "An organized kitchen makes cooking more enjoyable and efficient. The key is to create designated spaces for items, use appropriate storage solutions, and regularly reassess your organization system. Our store offers a wide range of kitchen organization products to help maximize your space and keep everything accessible. What specific area of your kitchen would you like to organize better?";
    }
}

/**
 * Get general kitchen information based on user query
 * @param string $message User message
 * @return string General kitchen information
 */
function getGeneralKitchenInfo($message) {
    if(containsAny($message, ['essential', 'basic', 'must have', 'starter'])) {
        return "Essential kitchen items for a well-equipped kitchen:\n\n• Quality chef's knife and cutting board\n• Stainless steel skillet and saucepan with lids\n• Mixing bowls and measuring tools\n• Silicone spatula, wooden spoon, and tongs\n• Baking sheet and casserole dish\n• Colander and fine mesh strainer\n• Basic kitchen appliances (based on your cooking style)\n\nThese versatile items will help you prepare most recipes. Would you like specific recommendations for any of these categories?";
    }
    else if(containsAny($message, ['maintain', 'maintenance', 'care', 'clean', 'cleaning'])) {
        return "Tips for kitchen maintenance and cleaning:\n\n• Clean as you go to prevent buildup\n• For wooden items, hand wash and occasionally oil\n• For cast iron, avoid soap and maintain seasoning\n• For non-stick surfaces, use non-abrasive cleaners and avoid metal utensils\n• Regularly sharpen knives for safety and efficiency\n• Deep clean appliances according to manufacturer instructions\n\nProper maintenance extends the life of your kitchen tools. Would you like specific care advice for particular items?";
    }
    else if(containsAny($message, ['sustainable', 'eco', 'green', 'environment'])) {
        return "Tips for a more sustainable kitchen:\n\n• Choose durable, long-lasting kitchenware over disposable items\n• Opt for materials like stainless steel, glass, wood, and cast iron\n• Use reusable food storage containers and silicone food covers\n• Consider energy-efficient appliances\n• Use biodegradable cleaning products\n• Reduce food waste with proper storage solutions\n\nOur store offers many eco-friendly kitchen options. Would you like to know more about sustainable kitchen products?";
    }
    else if(containsAny($message, ['safety', 'safe'])) {
        return "Kitchen safety tips:\n\n• Keep knives sharp (dull knives cause more accidents)\n• Use separate cutting boards for raw meat and produce\n• Turn pot handles inward to prevent accidental spills\n• Keep a fire extinguisher accessible\n• Use oven mitts or heat-resistant gloves for hot items\n• Clean spills immediately to prevent slips\n• Store chemicals away from food items\n\nSafety should always be a priority in the kitchen. Would you like recommendations for kitchen safety products?";
    }
    else {
        return "The kitchen is often called the heart of the home, where we prepare nourishing meals and create memories. A well-equipped kitchen with quality tools makes cooking more enjoyable and efficient. Our store aims to provide you with kitchen essentials that combine functionality, durability, and style. Is there a specific aspect of kitchen setup or maintenance you'd like to know more about?";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Accessories Stores Chatbot</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .chat-container {
            max-width: 500px;
            margin: 20px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .chat-header {
            background: #4a90e2;
            color: white;
            padding: 15px;
            text-align: center;
        }
        .chat-messages {
            padding: 20px;
            height: 400px;
            overflow-y: auto;
        }
        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
            max-width: 80%;
            word-wrap: break-word;
        }
        .user-message {
            background-color: #e6f2ff;
            margin-left: auto;
        }
        .bot-message {
            background-color: #f0f0f0;
            margin-right: auto;
        }
        .chat-input {
            display: flex;
            padding: 10px;
            border-top: 1px solid #ddd;
        }
        .chat-input input {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-right: 10px;
        }
        .chat-input button {
            background: #4a90e2;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        .chat-input button:hover {
            background: #357ae8;
        }
        .timestamp {
            font-size: 0.7em;
            color: #888;
            margin-top: 5px;
        }
        .typing-indicator {
            padding: 10px;
            background-color: #f0f0f0;
            border-radius: 5px;
            margin-bottom: 15px;
            display: none;
            width: 50px;
        }
        .typing-indicator span {
            height: 8px;
            width: 8px;
            background-color: #888;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
            animation: wave 1.5s infinite;
        }
        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }
        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
            margin-right: 0;
        }
        @keyframes wave {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-5px); }
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <h2>Kitchen Accessories Stores Helper</h2>
        </div>
        <div class="chat-messages" id="chatMessages">
            <!-- Bot welcome message -->
            <div class="message bot-message">
                <div class="message-content">Welcome to our Kitchen Accessories Stores ! I'm here to help you with any questions about kitchen products, cooking techniques, or our store services. How can I assist you today?</div>
                <div class="timestamp"><?php echo date('H:i'); ?></div>
            </div>
            <!-- Typing indicator -->
            <div class="typing-indicator" id="typingIndicator">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
        <div class="chat-input">
            <input type="text" id="userInput" placeholder="Type your message here...">
            <button id="sendButton">Send</button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatMessages = document.getElementById('chatMessages');
            const userInput = document.getElementById('userInput');
            const sendButton = document.getElementById('sendButton');
            const typingIndicator = document.getElementById('typingIndicator');
            
            // Function to add a message to the chat
            function addMessage(message, isUser = false) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${isUser ? 'user-message' : 'bot-message'}`;
                
                const contentDiv = document.createElement('div');
                contentDiv.className = 'message-content';
                contentDiv.textContent = message;
                
                const timestampDiv = document.createElement('div');
                timestampDiv.className = 'timestamp';
                const now = new Date();
                timestampDiv.textContent = now.getHours().toString().padStart(2, '0') + ':' + 
                                          now.getMinutes().toString().padStart(2, '0');
                
                messageDiv.appendChild(contentDiv);
                messageDiv.appendChild(timestampDiv);
                
                chatMessages.appendChild(messageDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            // Function to show typing indicator
            function showTypingIndicator() {
                typingIndicator.style.display = 'block';
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            // Function to hide typing indicator
            function hideTypingIndicator() {
                typingIndicator.style.display = 'none';
            }
            
            // Function to send message to server
            function sendMessage() {
                const message = userInput.value.trim();
                if (message === '') return;
                
                // Add user message to chat
                addMessage(message, true);
                userInput.value = '';
                
                // Show typing indicator
                showTypingIndicator();
                
                // Send message to server
                const formData = new FormData();
                formData.append('user_message', message);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Hide typing indicator
                    hideTypingIndicator();
                    
                    // Add bot response to chat
                    addMessage(data.response);
                })
                .catch(error => {
                    console.error('Error:', error);
                    hideTypingIndicator();
                    addMessage('Sorry, there was an error processing your request. Please try again.');
                });
            }
            
            // Event listeners
            sendButton.addEventListener('click', sendMessage);
            userInput.addEventListener('keypress', function(event) {
                if (event.key === 'Enter') {
                    sendMessage();
                }
            });
        });
    </script>
</body>
</html>