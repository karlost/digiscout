<?php

namespace Database\Factories;

use App\Models\MonitoringTool;
use Illuminate\Database\Eloquent\Factories\Factory;

class MonitoringToolFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MonitoringTool::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $codeCounter = 0;
        $codeCounter++;
        
        $toolNames = [
            'ping' => 'Ping Check',
            'http' => 'HTTP Status Check',
            'dns' => 'DNS Check',
            'load' => 'Load Time Check',
            'ssl' => 'SSL Certificate Check',
        ];
        
        // Create a unique code by adding a suffix to prevent unique constraint violations
        $baseCode = $this->faker->randomElement(array_keys($toolNames));
        $code = $baseCode . '_' . $codeCounter;
        
        return [
            'name' => $toolNames[$baseCode] . ' ' . $codeCounter,
            'code' => $code,
            'description' => $this->faker->paragraph(1),
            'default_interval' => $this->faker->numberBetween(5, 60),
            'interval_unit' => 'minute',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    
    /**
     * Configure the factory to create a ping tool.
     *
     * @return static
     */
    public function ping(): static
    {
        static $counter = 0;
        $counter++;
        
        return $this->state(fn (array $attributes) => [
            'name' => 'Ping Check',
            'code' => $counter > 1 ? "ping_{$counter}" : 'ping',
            'description' => 'Checks if the server responds to ping requests.',
        ]);
    }
    
    /**
     * Configure the factory to create an HTTP tool.
     *
     * @return static
     */
    public function http(): static
    {
        static $counter = 0;
        $counter++;
        
        return $this->state(fn (array $attributes) => [
            'name' => 'HTTP Status Check',
            'code' => $counter > 1 ? "http_{$counter}" : 'http',
            'description' => 'Checks HTTP status code of the website.',
        ]);
    }
    
    /**
     * Configure the factory to create a SSL tool.
     *
     * @return static
     */
    public function ssl(): static
    {
        static $counter = 0;
        $counter++;
        
        return $this->state(fn (array $attributes) => [
            'name' => 'SSL Certificate Check',
            'code' => $counter > 1 ? "ssl_{$counter}" : 'ssl',
            'description' => 'Checks SSL certificate validity and expiration.',
        ]);
    }
}