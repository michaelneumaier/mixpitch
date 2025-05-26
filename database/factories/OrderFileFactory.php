<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderFile>
 */
class OrderFileFactory extends Factory
{
    protected $model = OrderFile::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Ensure we have an order and a user to associate with
        // It's often better to specify the order_id and uploader_user_id when calling the factory
        // But we provide defaults here for basic usage.
        $order = Order::factory()->create(); 
        $uploader = $order->producer; // Default to producer uploading
        $type = OrderFile::TYPE_DELIVERY; // Default to delivery file
        $fileName = Str::random(10) . '.txt';
        $filePath = 'orders/' . $order->id . '/deliveries/' . $fileName;

        return [
            'order_id' => $order->id,
            'uploader_user_id' => $uploader->id, // Add the missing default
            'file_path' => $filePath,
            'file_name' => $fileName,
            'mime_type' => 'text/plain',
            'size' => $this->faker->numberBetween(100, 10000),
            'disk' => 's3', // Assuming s3 is default, adjust if needed
            'type' => $type,
        ];
    }

    // Optional: States for different file types
    public function requirement(): static
    {
        return $this->state(function (array $attributes) {
            $order = Order::find($attributes['order_id'] ?? Order::factory()->create());
            $fileName = Str::random(10) . '.pdf';
            return [
                'uploader_user_id' => $order->client_user_id, // Client uploads requirements
                'type' => OrderFile::TYPE_REQUIREMENT,
                'file_path' => 'orders/' . $order->id . '/requirements/' . $fileName,
                'file_name' => $fileName,
                'mime_type' => 'application/pdf',
            ];
        });
    }
    
     public function delivery(): static
    {
        return $this->state(function (array $attributes) {
            $order = Order::find($attributes['order_id'] ?? Order::factory()->create());
            $fileName = Str::random(10) . '.zip';
            return [
                'uploader_user_id' => $order->producer_user_id, // Producer uploads delivery
                'type' => OrderFile::TYPE_DELIVERY,
                'file_path' => 'orders/' . $order->id . '/deliveries/' . $fileName,
                 'file_name' => $fileName,
                 'mime_type' => 'application/zip',
            ];
        });
    }
}
