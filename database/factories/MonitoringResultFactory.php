<?php

namespace Database\Factories;

use App\Models\MonitoringResult;
use App\Models\Website;
use App\Models\MonitoringTool;
use Illuminate\Database\Eloquent\Factories\Factory;

class MonitoringResultFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MonitoringResult::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['success', 'warning', 'failure'];
        $status = $this->faker->randomElement($statuses);
        
        $value = match($status) {
            'success' => $this->faker->randomFloat(2, 10, 100),
            'warning' => $this->faker->randomFloat(2, 100, 500),
            'failure' => $this->faker->randomFloat(2, 0, 10),
            default => 0,
        };
        
        $messages = [
            'success' => [
                'HTTP status 200 - OK',
                'Ping successful with {value}ms latency', 
                'SSL certificate valid for {days} days',
            ],
            'warning' => [
                'High response time: {value}ms',
                'SSL certificate expiring soon: {days} days left',
                'Redirect detected to {url}',
            ],
            'failure' => [
                'HTTP status 500 - Server Error',
                'Connection timed out',
                'SSL certificate expired',
                'DNS lookup failed',
            ],
        ];
        
        $message = $this->faker->randomElement($messages[$status]);
        $message = str_replace('{value}', round($value, 2), $message);
        $message = str_replace('{days}', rand(1, 30), $message);
        $message = str_replace('{url}', $this->faker->url(), $message);
        
        return [
            'website_id' => Website::factory(),
            'monitoring_tool_id' => MonitoringTool::factory(),
            'status' => $status,
            'value' => $value,
            'check_time' => $this->faker->dateTimeBetween('-1 day', 'now'),
            'additional_data' => [
                'message' => $message,
                'details' => $this->faker->paragraph(),
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    
    /**
     * Configure the factory to create a success result.
     *
     * @return static
     */
    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'value' => $this->faker->randomFloat(2, 10, 100),
            'additional_data' => [
                'message' => 'Check completed successfully',
                'details' => 'Everything is working as expected.',
            ],
        ]);
    }
    
    /**
     * Configure the factory to create a warning result.
     *
     * @return static
     */
    public function warning(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'warning',
            'value' => $this->faker->randomFloat(2, 100, 500),
            'additional_data' => [
                'message' => 'Warning: High response time',
                'details' => 'The website is responding, but slower than expected.',
            ],
        ]);
    }
    
    /**
     * Configure the factory to create a failure result.
     *
     * @return static
     */
    public function failure(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failure',
            'value' => 0,
            'additional_data' => [
                'message' => 'Error: Connection failed',
                'details' => 'Unable to connect to the website server.',
            ],
        ]);
    }
}