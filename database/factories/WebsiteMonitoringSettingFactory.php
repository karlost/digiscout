<?php

namespace Database\Factories;

use App\Models\WebsiteMonitoringSetting;
use App\Models\Website;
use App\Models\MonitoringTool;
use Illuminate\Database\Eloquent\Factories\Factory;

class WebsiteMonitoringSettingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WebsiteMonitoringSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'website_id' => Website::factory(),
            'monitoring_tool_id' => MonitoringTool::factory(),
            'interval' => $this->faker->numberBetween(5, 60),
            'enabled' => $this->faker->boolean(90), // 90% chance of being enabled
            'threshold' => $this->faker->optional(0.7)->randomFloat(2, 0.5, 10.0), // 70% chance of having a threshold
            'notify' => $this->faker->boolean(60), // 60% chance of notifications
            'notify_discord' => $this->faker->boolean(30), // 30% chance of Discord notifications
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    
    /**
     * Configure the factory to create an enabled setting.
     *
     * @return static
     */
    public function enabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => true,
        ]);
    }
    
    /**
     * Configure the factory to create a disabled setting.
     *
     * @return static
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => false,
        ]);
    }
    
    /**
     * Configure the factory to create a setting with notifications enabled.
     *
     * @return static
     */
    public function withNotifications(): static
    {
        return $this->state(fn (array $attributes) => [
            'notify' => true,
            'notify_discord' => true,
        ]);
    }
}