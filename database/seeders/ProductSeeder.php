<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create vendor role if it doesn't exist
        $vendorRole = Role::where('name', 'vendor')->first();
        if (!$vendorRole) {
            $vendorRole = Role::create([
                'name' => 'vendor',
                'description' => 'Vendor role with permissions to sell products'
            ]);
        }

        // Create a test vendor user
        $vendorUser = User::where('email', 'vendor@example.com')->first();
        if (!$vendorUser) {
            $vendorUser = User::create([
                'name' => 'Test Vendor',
                'email' => 'vendor@example.com',
                'phone_number' => '1234567890',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]);

            // Attach vendor role
            $vendorUser->roles()->attach($vendorRole->id);
        }

        // Create vendor profile
        $vendor = Vendor::where('user_id', $vendorUser->id)->first();
        if (!$vendor) {
            $vendor = Vendor::create([
                'user_id' => $vendorUser->id,
                'store_name' => 'Tech Gadgets Store',
                'slug' => 'tech-gadgets-store',
                'description' => 'We sell the latest and greatest tech gadgets at competitive prices.',
                'contact_email' => 'contact@techgadgets.com',
                'contact_phone' => '123-456-7890',
                'address_line1' => '123 Tech Street',
                'city' => 'San Francisco',
                'state' => 'CA',
                'postal_code' => '94105',
                'country' => 'United States',
                'status' => 'active',
            ]);
        }

        // Create another vendor
        $vendorUser2 = User::where('email', 'vendor2@example.com')->first();
        if (!$vendorUser2) {
            $vendorUser2 = User::create([
                'name' => 'Fashion Vendor',
                'email' => 'vendor2@example.com',
                'phone_number' => '9876543210',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]);

            // Attach vendor role
            $vendorUser2->roles()->attach($vendorRole->id);
        }

        // Create vendor profile
        $vendor2 = Vendor::where('user_id', $vendorUser2->id)->first();
        if (!$vendor2) {
            $vendor2 = Vendor::create([
                'user_id' => $vendorUser2->id,
                'store_name' => 'Fashion Forward',
                'slug' => 'fashion-forward',
                'description' => 'Trendy fashion items for every season.',
                'contact_email' => 'contact@fashionforward.com',
                'contact_phone' => '987-654-3210',
                'address_line1' => '456 Fashion Avenue',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'United States',
                'status' => 'active',
            ]);
        }

        // Product data with SKUs added
        $techProducts = [
            [
                'name' => 'Wireless Bluetooth Headphones',
                'sku' => 'TG-WBH-001',  // Added SKU
                'description' => 'High-quality wireless headphones with noise cancellation features. Perfect for music lovers and remote workers.',
                'price' => 99.99,
                'stock_quantity' => 50,
                'main_image_path' => 'products/headphones.jpg',
                'featured' => true,
            ],
            [
                'name' => 'Smart Watch Series 5',
                'sku' => 'TG-SWS-001',  // Added SKU
                'description' => 'Track your fitness goals, check notifications, and more with this latest smartwatch. Water-resistant and long battery life.',
                'price' => 249.99,
                'stock_quantity' => 30,
                'main_image_path' => 'products/smartwatch.jpg',
                'featured' => true,
            ],
            [
                'name' => '4K Ultra HD Smart TV - 55"',
                'sku' => 'TG-TV4K-001',  // Added SKU
                'description' => 'Immerse yourself in stunning 4K resolution with this smart TV. Stream your favorite content easily.',
                'price' => 599.99,
                'stock_quantity' => 15,
                'main_image_path' => 'products/tv.jpg',
                'featured' => false,
            ],
            [
                'name' => 'Portable Bluetooth Speaker',
                'sku' => 'TG-PBS-001',  // Added SKU
                'description' => 'Take your music anywhere with this portable, waterproof Bluetooth speaker. Up to 20 hours of battery life.',
                'price' => 79.99,
                'stock_quantity' => 75,
                'main_image_path' => 'products/speaker.jpg',
                'featured' => false,
            ],
            [
                'name' => 'Wireless Charging Pad',
                'sku' => 'TG-WCP-001',  // Added SKU
                'description' => 'Qi-certified wireless charger compatible with the latest smartphones. Sleek design fits any desk setup.',
                'price' => 29.99,
                'stock_quantity' => 100,
                'main_image_path' => 'products/charger.jpg',
                'featured' => false,
            ]
        ];

        $fashionProducts = [
            [
                'name' => 'Classic Denim Jacket',
                'sku' => 'FF-CDJ-001',  // Added SKU
                'description' => 'A timeless denim jacket that goes with everything in your wardrobe. Made from premium quality denim.',
                'price' => 59.99,
                'stock_quantity' => 40,
                'main_image_path' => 'products/denim_jacket.jpg',
                'featured' => true,
            ],
            [
                'name' => 'Summer Floral Dress',
                'sku' => 'FF-SFD-001',  // Added SKU
                'description' => 'Light and flowy dress perfect for summer days. Features a beautiful floral pattern.',
                'price' => 45.99,
                'stock_quantity' => 25,
                'main_image_path' => 'products/floral_dress.jpg',
                'featured' => true,
            ],
            [
                'name' => 'Leather Crossbody Bag',
                'sku' => 'FF-LCB-001',  // Added SKU
                'description' => 'Stylish yet practical genuine leather crossbody bag. Multiple compartments for organization.',
                'price' => 89.99,
                'stock_quantity' => 20,
                'main_image_path' => 'products/leather_bag.jpg',
                'featured' => false,
            ],
            [
                'name' => 'Men\'s Classic Oxford Shirt',
                'sku' => 'FF-MCOS-001',  // Added SKU
                'description' => 'A wardrobe essential for every man. This oxford shirt is made from 100% cotton for maximum comfort.',
                'price' => 39.99,
                'stock_quantity' => 60,
                'main_image_path' => 'products/oxford_shirt.jpg',
                'featured' => false,
            ],
            [
                'name' => 'Designer Sunglasses',
                'sku' => 'FF-DS-001',  // Added SKU
                'description' => 'Protect your eyes in style with these UV-protected designer sunglasses. Comes with a premium case.',
                'price' => 129.99,
                'stock_quantity' => 35,
                'main_image_path' => 'products/sunglasses.jpg',
                'featured' => false,
            ]
        ];

        // Create tech products
        foreach ($techProducts as $productData) {
            $slug = Str::slug($productData['name']);
            $count = Product::where('slug', 'LIKE', "{$slug}%")->count();
            if ($count > 0) {
                $slug = "{$slug}-{$count}";
            }
            
            Product::create([
                'vendor_id' => $vendor->id,
                'name' => $productData['name'],
                'slug' => $slug,
                'sku' => $productData['sku'],  // Added SKU here
                'description' => $productData['description'],
                'price' => $productData['price'],
                'stock_quantity' => $productData['stock_quantity'],
                'main_image_path' => $productData['main_image_path'],
                'featured' => $productData['featured'],
                'status' => 'published',
                'short_description' => substr($productData['description'], 0, 100), // Optional
                'stock_status' => 'in_stock',
            ]);
        }
        
        // Update vendor product count
        $vendor->total_products = count($techProducts);
        $vendor->save();

        // Create fashion products
        foreach ($fashionProducts as $productData) {
            $slug = Str::slug($productData['name']);
            $count = Product::where('slug', 'LIKE', "{$slug}%")->count();
            if ($count > 0) {
                $slug = "{$slug}-{$count}";
            }
            
            Product::create([
                'vendor_id' => $vendor2->id,
                'name' => $productData['name'],
                'slug' => $slug,
                'sku' => $productData['sku'],  // Added SKU here
                'description' => $productData['description'],
                'price' => $productData['price'],
                'stock_quantity' => $productData['stock_quantity'],
                'main_image_path' => $productData['main_image_path'],
                'featured' => $productData['featured'], 
                'status' => 'published',
                'short_description' => substr($productData['description'], 0, 100), // Optional
                'stock_status' => 'in_stock',
            ]);
        }
        
        // Update vendor product count
        $vendor2->total_products = count($fashionProducts);
        $vendor2->save();

        $this->command->info('Sample products have been added successfully!');
    }
}
