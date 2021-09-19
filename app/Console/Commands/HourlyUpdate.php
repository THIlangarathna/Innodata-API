<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class HourlyUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hour:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete Temp Folders';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $path = (public_path('storage\zip\temp\\'));
        $filelastmodified = filemtime($path);
        if ($handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if($file == '.' or $file == '..')
                {
                    continue;
                }
            $filelastmodified = filemtime($path . $file);
            if((time() - $filelastmodified) > 14400)
            {
                File::deleteDirectory($path . $file);
            }
            }
            closedir($handle);
        }

        $this->info('Hourly Update has been performed successfully');
    }
}
