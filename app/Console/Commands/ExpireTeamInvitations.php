<?php

namespace App\Console\Commands;

use App\Models\TeamInvitation;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExpireTeamInvitations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invitations:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark expired team invitations as expired';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        
        // Tìm tất cả lời mời đã hết hạn nhưng vẫn đang ở trạng thái pending
        $expiredInvitations = TeamInvitation::where('status', 'pending')
            ->where('expires_at', '<', $now)
            ->get();
            
        $count = $expiredInvitations->count();
        
        if ($count === 0) {
            $this->info('No expired invitations found.');
            return 0;
        }
        
        // Đánh dấu tất cả lời mời đã hết hạn
        foreach ($expiredInvitations as $invitation) {
            $invitation->markAsExpired();
        }
        
        $this->info("Successfully marked {$count} expired invitations.");
        
        return 0;
    }
}
