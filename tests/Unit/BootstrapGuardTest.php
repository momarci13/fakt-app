<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The web server's PHP version is chosen per subdomain in cPanel's MultiPHP
 * Manager, while every cron job pins /usr/local/bin/ea-php83 by full path. When
 * those disagree, artisan stays green and every browser request dies inside the
 * autoloader, before Laravel can log a single line.
 *
 * The entry points therefore carry a version guard that must run on the OLDEST
 * PHP the account can be switched to. PHP parses a whole file before executing
 * any of it, so a single PHP 8 only construct anywhere above the autoloader
 * turns the guard back into the blank HTTP 500 it exists to prevent.
 */
class BootstrapGuardTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public static function entryPoints(): array
    {
        return [
            'cPanel public index' => [__DIR__.'/../../deploy/cpanel-public-index.php'],
            'default public index' => [__DIR__.'/../../public/index.php'],
        ];
    }

    #[DataProvider('entryPoints')]
    public function test_the_entry_point_refuses_php_older_than_the_composer_requirement(string $path): void
    {
        $this->assertFileExists($path);
        $this->assertStringContainsString(
            'PHP_VERSION_ID < 80300',
            (string) file_get_contents($path),
            'A belépési pontnak ellenőriznie kell a PHP verziót, mielőtt betölti a Composer autoloadert.'
        );
    }

    #[DataProvider('entryPoints')]
    public function test_nothing_above_the_autoloader_needs_php_8(string $path): void
    {
        $source = (string) file_get_contents($path);

        $this->assertSame(
            1,
            preg_match('~require(?:_once)?\s+[^;]*vendor/autoload\.php~', $source, $matches, PREG_OFFSET_CAPTURE),
            'Nem található a vendor/autoload.php betöltése.'
        );

        $prelude = substr($source, 0, (int) $matches[0][1]);

        $this->assertStringContainsString(
            'PHP_VERSION_ID < 80300',
            $prelude,
            'A verzióellenőrzésnek az autoloader betöltése ELŐTT kell futnia.'
        );

        $forbidden = [
            'str_starts_with(' => 'PHP 8.0 függvény',
            'str_contains(' => 'PHP 8.0 függvény',
            'str_ends_with(' => 'PHP 8.0 függvény',
            '?->' => 'PHP 8.0 nullsafe operátor',
            '??' => 'PHP 7.0 null coalescing',
            'match (' => 'PHP 8.0 match kifejezés',
        ];

        foreach ($forbidden as $needle => $reason) {
            $this->assertStringNotContainsString(
                $needle,
                $prelude,
                "Az autoloader előtti rész nem tartalmazhat {$reason} elemet ({$needle}), különben a régi PHP már a fájl értelmezésekor elhasal, és a védelem sosem fut le."
            );
        }

        $this->assertSame(
            0,
            preg_match('~dirname\s*\([^;]*,\s*\d~', $prelude),
            'A dirname() második paramétere PHP 7.0+; használj beágyazott dirname() hívást.'
        );
    }
}
