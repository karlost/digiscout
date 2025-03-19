<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Website;
use App\Models\MonitoringTool;
use App\Models\MonitoringResult;
use App\Notifications\MonitoringFailureNotification;
use Illuminate\Console\Command;

class SendTestNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:test {--all : Send to all users} {--user= : User ID to send notification to} {--message= : Custom message for the notification}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test notification to user(s)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $message = $this->option('message') ?? 'Toto je testovací zpráva notifikačního systému.';
        
        if ($this->option('all')) {
            $users = User::where('is_admin', true)->get();
            
            if ($users->isEmpty()) {
                $this->error('No admin users found to send notifications to.');
                return 1;
            }
            
            $count = 0;
            foreach ($users as $user) {
                $this->sendTestNotification($user, $message);
                $count++;
            }
            
            $this->info("Test notifications sent to {$count} admin users.");
            return 0;
        }
        
        $userId = $this->option('user');
        if (!$userId) {
            $userId = $this->ask('Enter the user ID to send the notification to:');
        }
        
        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return 1;
        }
        
        $this->sendTestNotification($user, $message);
        $this->info("Test notification sent to user {$user->name}.");
        
        return 0;
    }
    
    /**
     * Send a test notification to a user.
     */
    protected function sendTestNotification(User $user, string $message): void
    {
        $data = [
            'website_id' => 0,
            'website_name' => 'Test Website',
            'website_url' => 'https://example.com',
            'tool_id' => 0,
            'tool_name' => 'Test Tool',
            'result_id' => 0,
            'message' => $message,
            'check_time' => now()->toIso8601String(),
        ];
        
        // Create a mock result for the notification
        $mockResult = new MonitoringResult([
            'id' => 0,
            'website_id' => 0,
            'monitoring_tool_id' => 0,
            'status' => 'error',
            'check_time' => now(),
            'additional_data' => ['message' => $message]
        ]);
        
        // Add relationships
        $mockResult->website = new Website([
            'id' => 0,
            'name' => 'Test Website',
            'url' => 'https://example.com'
        ]);
        
        $mockResult->monitoringTool = new MonitoringTool([
            'id' => 0,
            'name' => 'Test Tool'
        ]);
        
        $user->notify(new MonitoringFailureNotification($mockResult));
    }
}
