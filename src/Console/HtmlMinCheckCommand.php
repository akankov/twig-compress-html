<?php

declare(strict_types=1);

namespace Akankov\TwigCompressHtml\Console;

use Akankov\HtmlMin\HtmlMin;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * `bin/console html-min:check path/to/file.html` — minifies the given file
 * in memory and prints the byte savings. Useful as a CI/dev smoke-check
 * ("did our last template change inflate the page?") without modifying the
 * file on disk. Mirrors the Laravel binding's `html-min:check` Artisan
 * command.
 */
#[AsCommand(
    name: 'html-min:check',
    description: 'Minify an HTML file and report the byte savings without writing changes to disk.',
)]
final class HtmlMinCheckCommand extends Command
{
    public function __construct(private readonly HtmlMin $minifier)
    {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'Path to an HTML file to minify and measure');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $file = $input->getArgument('file');
        // A single @file_get_contents already reports missing / unreadable /
        // directory / non-string paths via false — no redundant is_file()/
        // is_readable() pre-check (mirrors the engine's Cli::readInput()).
        $original = \is_string($file) ? @file_get_contents($file) : false;
        if ($original === false) {
            $io->error(\sprintf('Cannot read input file: %s', \is_string($file) ? $file : '<missing>'));

            return Command::FAILURE;
        }

        $minified = $this->minifier->minify($original);
        $beforeBytes = \strlen($original);
        $afterBytes = \strlen($minified);
        $savings = $beforeBytes - $afterBytes;
        $percent = $beforeBytes > 0 ? ($savings / $beforeBytes) * 100 : 0.0;

        $io->success(\sprintf(
            'Reduced from %s to %s (%s%.1f%%)',
            self::formatBytes($beforeBytes),
            self::formatBytes($afterBytes),
            $savings >= 0 ? '-' : '+',
            abs($percent),
        ));

        return Command::SUCCESS;
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return \sprintf('%.1f MB', $bytes / 1024.0 / 1024.0);
        }

        if ($bytes >= 1024) {
            return \sprintf('%.1f KB', $bytes / 1024.0);
        }

        return $bytes . ' B';
    }
}
