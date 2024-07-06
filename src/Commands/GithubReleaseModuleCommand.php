<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/cms
 * @author     The Anh Dang
 * @link       https://juzaweb.com
 * @license    GNU V2
 */

namespace LarabizCMS\ForDev\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class GithubReleaseModuleCommand extends Command
{
    protected $name = 'github:release-module';

    protected $description = 'Create release module';

    public function handle(): void
    {
        $token = $this->getGithubToken();
        $repo = $this->getRepo();
        $lastTag = $this->getLastTag();

        if (empty($lastTag)) {
            $body = $this->runCmd("git log --no-merges --pretty=format:\"* %s\" | uniq");
        } else {
            $body = $this->runCmd("git log --no-merges --pretty=format:\"* %s\" {$lastTag}..HEAD | uniq");
        }

        if (empty($body)) {
            $this->info('Nothing to release');
            return;
        }

        $body = collect(explode("\n", $body))
            ->filter(fn ($item) => !empty($item) && !str_contains($item, ':construction:'))
            ->implode("\n");

        if ($this->option('preview')) {
            $this->info($body);
            return;
        }

        $newTag = $this->getReleaseVersion($lastTag);

        if ($this->option('changelog')) {
            $this->info('Add changelog');

            File::prepend(
                base_path($this->argument('path')."/changelog.md"),
                "### v{$newTag} \n{$body}\n\n"
            );

            $this->runCmd('git add changelog.md');

            $this->runCmd("git commit -o changelog.md -m ':memo: Add changelog v{$newTag}'");

            $this->runCmd('git push');
        }

        $this->info("Release v{$newTag}");

        $release = Http::withHeaders(
            [
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
            ]
        )
            ->post(
                "https://api.github.com/repos/{$repo}/releases",
                [
                    'tag_name' => $newTag,
                    'name' => $newTag,
                    'target_commitish' => $this->option('target'),
                    'body' => $body,
                ]
            )
            ->throw();

        $this->info('Released url: '. $release->json()['html_url']);
    }

    protected function getLastTag(): string
    {
        try {
            $lastTag = $this->runCmd('git ls-remote --tags --sort=-committerdate | head -1');
        } catch (\Exception $e) {
            $lastTag = '';
        }

        return last(explode('/', $lastTag));
    }

    protected function getGithubToken(): string
    {
        $token = config('dev-tool.release.github_token');
        if (empty($token)) {
            do {
                $token = $this->ask('Please enter your github token: ');
            } while (empty($token));
        }

        return $token;
    }

    protected function getReleaseVersion(string $lastTag): string
    {
        if ($version = $this->option('ver')) {
            return $version;
        }

        if (empty($lastTag)) {
            return '1.0.0';
        }

        $split = explode('.', $lastTag);
        if (count($split) > 2) {
            ++$split[count($split) - 1];
            $newTag = implode('.', $split);
        } else {
            $newTag = $lastTag.'.1';
        }

        return str_replace('v', '', $newTag);
    }
    
    protected function runCmd(string|array $command): string
    {
        $path = $this->argument('path');

        if (!File::isDirectory($path)) {
            $path = base_path($path);
        }

        if (is_array($command)) {
            $process = new Process($command, $path);
        } else {
            $process = Process::fromShellCommandline($command, $path);
        }

        $process->setTimeout(30);

        $process->mustRun();

        return trim($process->getOutput());
    }

    protected function getRepo(): string
    {
        $repo = $this->runCmd('git config --get remote.origin.url | sed \'s/.*[:|\/]\([^/]*\/[^/]*\)\.git$/\1/\'');

        if (filter_var($repo, FILTER_VALIDATE_URL) !== false) {
            $repo = ltrim(parse_url($repo, \PHP_URL_PATH), '/');
        }

        return $repo;
    }

    protected function getArguments(): array
    {
        return [
            ['path', InputArgument::REQUIRED, 'Module path.'],
        ];
    }
    
    protected function getOptions(): array
    {
        return [
            ['ver', null, InputOption::VALUE_OPTIONAL, 'Version to release. Auto increment version if not set', null],
            ['changelog', null, InputOption::VALUE_OPTIONAL, 'Write to changelog.md. Default: true', true],
            ['target', null, InputOption::VALUE_OPTIONAL, 'Target branch to release', 'master'],
            ['preview', null, InputOption::VALUE_NONE, 'Preview release'],
        ];
    }
}
