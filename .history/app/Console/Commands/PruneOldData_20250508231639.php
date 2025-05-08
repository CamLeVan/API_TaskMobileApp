<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SyncStatus;
use App\Models\DeviceToken;
use App\Models\Attachment;
use Carbon\Carbon;

class PruneOldData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:prune-old-data
                            {--days=30 : Số ngày giữ lại dữ liệu}
                            {--inactive-devices=90 : Số ngày không hoạt động trước khi xóa thiết bị}
                            {--sync-status : Dọn dẹp trạng thái đồng bộ}
                            {--device-tokens : Dọn dẹp token thiết bị không hoạt động}
                            {--temp-files : Dọn dẹp tệp tạm}
                            {--all : Dọn dẹp tất cả}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dọn dẹp dữ liệu cũ trong hệ thống';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $inactiveDays = $this->option('inactive-devices');
        $cutoffDate = Carbon::now()->subDays($days);
        $inactiveCutoffDate = Carbon::now()->subDays($inactiveDays);

        $this->info("Bắt đầu dọn dẹp dữ liệu cũ hơn {$days} ngày...");

        // Dọn dẹp trạng thái đồng bộ
        if ($this->option('sync-status') || $this->option('all')) {
            $this->cleanupSyncStatus($cutoffDate);
        }

        // Dọn dẹp token thiết bị không hoạt động
        if ($this->option('device-tokens') || $this->option('all')) {
            $this->cleanupDeviceTokens($inactiveCutoffDate);
        }

        // Dọn dẹp tệp tạm
        if ($this->option('temp-files') || $this->option('all')) {
            $this->cleanupTempFiles($cutoffDate);
        }

        $this->info('Hoàn tất dọn dẹp dữ liệu.');

        return Command::SUCCESS;
    }

    /**
     * Dọn dẹp trạng thái đồng bộ cũ
     */
    private function cleanupSyncStatus($cutoffDate)
    {
        $count = SyncStatus::where('created_at', '<', $cutoffDate)->delete();
        $this->info("Đã xóa {$count} bản ghi trạng thái đồng bộ cũ.");
    }

    /**
     * Dọn dẹp token thiết bị không hoạt động
     */
    private function cleanupDeviceTokens($cutoffDate)
    {
        $count = DeviceToken::where('last_used_at', '<', $cutoffDate)->delete();
        $this->info("Đã xóa {$count} token thiết bị không hoạt động.");
    }

    /**
     * Dọn dẹp tệp tạm
     */
    private function cleanupTempFiles($cutoffDate)
    {
        $count = Attachment::where('is_temp', true)
            ->where('created_at', '<', $cutoffDate)
            ->get();

        foreach ($count as $attachment) {
            // Xóa tệp vật lý
            if (file_exists($attachment->path)) {
                unlink($attachment->path);
            }

            // Xóa bản ghi
            $attachment->delete();
        }

        $this->info("Đã xóa " . count($count) . " tệp tạm.");
    }