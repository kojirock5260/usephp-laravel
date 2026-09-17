<?php

declare(strict_types=1);

namespace Kojirock5260\UsePhpLaravel\Console;

use Illuminate\Console\Command;
use Polidog\UsePhp\Psx\CompileCommand as UsePhpCompileCommand;

final class CompileCommand extends Command
{
    protected $signature = 'usephp:compile
        {--check : Verify the cache is up to date without writing (CI)}
        {--clean : Remove the compiled cache}';

    protected $description = 'Compile .psx components into the PSX cache';

    public function handle(): int
    {
        $components = (string) config('usephp.components_path');
        $cache = (string) config('usephp.cache_path');

        $argv = [$components, '--cache=' . $cache];
        foreach (['check', 'clean'] as $flag) {
            if ($this->option($flag)) {
                $argv[] = '--' . $flag;
            }
        }

        return (new UsePhpCompileCommand())->run($argv, $components);
    }
}
