<?php

namespace Lightworx\FilamentPwa\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class DownloadFlagsCommand extends Command
{
    protected $signature   = 'pwa:download-flags {--force : Re-download even if flags already exist}';
    protected $description = 'Download country flag images for the PWA phone picker';

    /**
     * All ISO codes used in the package's country list.
     * Matches the $allCountries array in user-menu.blade.php exactly.
     */
    private const ISO_CODES = [
        'za','us','gb','au','nz','ca','ng','ke','gh','zw','zm','bw','na','mz',
        'tz','ug','rw','et','eg','ma','dz','tn','sn','ci','cm','ao','in','pk',
        'bd','lk','np','ph','id','my','sg','th','vn','cn','jp','kr','de','fr',
        'it','es','pt','nl','be','ch','at','se','no','dk','fi','pl','cz','hu',
        'ro','gr','tr','ru','ua','il','ae','sa','qa','kw','bh','om','jo','lb',
        'iq','ir','br','ar','mx','co','cl','pe','ve','ec','bo','uy','py',
    ];

    /** Source URL pattern — 20×15px PNGs, ~1–3 KB each */
    private const CDN_URL = 'https://flagcdn.com/20x15/{iso}.png';

    public function handle(): int
    {
        $destination = public_path('pwa/flags');

        // Check if already downloaded
        if (!$this->option('force') && File::isDirectory($destination)) {
            $existing = count(File::glob($destination . '/*.png'));
            if ($existing >= count(self::ISO_CODES)) {
                $this->info("  Flags already downloaded ({$existing} files). Use --force to re-download.");
                return self::SUCCESS;
            }
        }

        File::ensureDirectoryExists($destination);

        $this->info('Downloading country flag images…');
        $bar     = $this->output->createProgressBar(count(self::ISO_CODES));
        $bar->start();

        $failed = [];

        foreach (self::ISO_CODES as $iso) {
            $dest = $destination . '/' . $iso . '.png';

            // Skip existing unless --force
            if (!$this->option('force') && File::exists($dest)) {
                $bar->advance();
                continue;
            }

            try {
                $url      = str_replace('{iso}', $iso, self::CDN_URL);
                $response = Http::timeout(10)->get($url);

                if ($response->successful()) {
                    File::put($dest, $response->body());
                } else {
                    $failed[] = $iso . ' (HTTP ' . $response->status() . ')';
                }
            } catch (\Throwable $e) {
                $failed[] = $iso . ' (' . $e->getMessage() . ')';
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $downloaded = count(self::ISO_CODES) - count($failed);
        $this->info("  ✓ {$downloaded} flags downloaded to public/pwa/flags/");

        if (!empty($failed)) {
            $this->warn('  The following flags could not be downloaded:');
            foreach ($failed as $f) {
                $this->line("    - {$f}");
            }
            $this->line('  The picker will show the ISO code text for missing flags.');
        }

        return empty($failed) ? self::SUCCESS : self::FAILURE;
    }
}