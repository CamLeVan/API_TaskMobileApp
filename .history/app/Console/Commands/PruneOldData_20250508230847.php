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