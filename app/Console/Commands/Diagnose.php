<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Reproduces a real HTTP request inside the CLI process and prints the exception
 * that the browser only shows as a blank "500 Server Error" page.
 *
 * Nothing is exposed publicly: the command is CLI-only, APP_DEBUG stays false,
 * and no route or file is added to the web-facing document root.
 */
class Diagnose extends Command
{
    protected $signature = 'fakt:diagnose
        {--path=/login : A vizsgalando utvonal, peldaul /login vagy /dashboard}
        {--host= : Felulirja a keres hosztnevet (alapertelmezes: APP_URL hosztja)}
        {--trace=20 : Hany stack trace sort irjon ki kivetelenkent}
        {--log-lines=40 : Hany sort mutasson a legfrissebb Laravel naplobol}';

    protected $description = 'Feltarja a weboldal HTTP 500 hibajanak valodi okat, APP_DEBUG bekapcsolasa nelkul.';

    /** @var array<int, Throwable> */
    public static array $captured = [];

    public function handle(): int
    {
        self::$captured = [];

        $this->line(str_repeat('=', 72));
        $this->line('FAKT diagnosztika - '.date('Y-m-d H:i:s T'));
        $this->line(str_repeat('=', 72));

        $failures = 0;

        $failures += $this->sectionEnvironment();
        $failures += $this->sectionEncryption();
        $failures += $this->sectionFilesystem();
        $failures += $this->sectionDatabase();
        $failures += $this->sectionLogging();
        $failures += $this->sectionHttp();

        $this->tailBootstrapLog();
        $this->tailLaravelLog();

        $this->newLine();
        $this->line(str_repeat('=', 72));
        $this->line($failures === 0
            ? '[OSSZEGZES] Nem talaltam hibat. Ha a bongeszo megis hibazik, futtasd ujra a --path kapcsoloval arra az oldalra, ahol a hiba jelentkezik.'
            : '[OSSZEGZES] '.$failures.' hibapont. A fenti [HIBA] sorok a javitando okok.');
        $this->line(str_repeat('=', 72));

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function sectionEnvironment(): int
    {
        $this->header('1. Kornyezet');

        $this->kv('PHP', PHP_VERSION.' ('.PHP_SAPI.')');
        $this->line('    ^ ez a CLI PHP verzio. A weboldalt a cPanel MultiPHP Manager szerinti PHP futtatja,');
        $this->line('      ami ettol elterhet. Elteres eseten a weboldal 500-at ad, a cron viszont zold marad.');
        $this->kv('Laravel', app()->version());
        $this->kv('base_path', base_path());
        $this->kv('public_path', public_path());
        $this->kv('APP_ENV', (string) config('app.env'));
        $this->kv('APP_DEBUG', config('app.debug') ? 'true' : 'false');
        $this->kv('APP_URL', (string) config('app.url'));
        $this->kv('APP_TRUSTED_HOST', (string) config('security.trusted_host'));
        $this->kv('config gyorsitotar', app()->configurationIsCached() ? 'igen' : 'nem');
        $this->kv('route gyorsitotar', app()->routesAreCached() ? 'igen' : 'nem');
        $this->kv('event gyorsitotar', app()->eventsAreCached() ? 'igen' : 'nem');
        $this->kv('session.driver', (string) config('session.driver'));
        $this->kv('cache.default', (string) config('cache.default'));
        $this->kv('db.default', (string) config('database.default'));

        $failures = 0;

        $appHost = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        $trusted = strtolower((string) config('security.trusted_host'));

        if ($trusted === '') {
            $this->error('[HIBA] Az APP_TRUSTED_HOST ures. Az EnforceTrustedHost minden production keresre HTTP 400-at ad.');
            $failures++;
        } elseif ($appHost !== '' && $trusted !== strtolower($appHost)) {
            $this->error('[HIBA] APP_TRUSTED_HOST ('.$trusted.') nem egyezik az APP_URL hosztjaval ('.$appHost.').');
            $failures++;
        }

        $maintenance = storage_path('framework/down');
        if (is_file($maintenance)) {
            $this->error('[HIBA] Az alkalmazas maintenance modban van ('.$maintenance.'). Futtasd: artisan up');
            $failures++;
        }

        return $failures;
    }

    private function sectionEncryption(): int
    {
        $this->header('2. APP_KEY es titkositas');

        $key = (string) config('app.key');
        $cipher = (string) config('app.cipher');

        $this->kv('cipher', $cipher);
        $this->kv('APP_KEY beallitva', $key === '' ? 'NEM' : 'igen');

        if ($key === '') {
            $this->error('[HIBA] Nincs APP_KEY. Minden HTTP keres 500-at ad, mikozben az artisan parancsok hibatlanul futnak.');

            return 1;
        }

        $raw = Str::startsWith($key, 'base64:') ? base64_decode(substr($key, 7), true) : $key;

        if ($raw === false) {
            $this->error('[HIBA] Az APP_KEY nem ervenyes base64 ertek.');

            return 1;
        }

        $this->kv('kulcs hossza', strlen($raw).' bajt');

        if (! Encrypter::supported($raw, $cipher)) {
            $this->error('[HIBA] Az APP_KEY hossza nem illik a(z) '.$cipher.' cipherhez. AES-256-CBC eseten 32 bajt kell.');
            $this->line('        Ez pontosan az a hiba, ami CLI-ben lathatatlan, bongeszoben viszont minden oldalon 500.');

            return 1;
        }

        try {
            $probe = encrypt('fakt-diagnose');

            if (decrypt($probe) !== 'fakt-diagnose') {
                $this->error('[HIBA] A titkositas oda-vissza teszt nem egyezik.');

                return 1;
            }

            $this->line('  [OK] Titkositas oda-vissza teszt sikeres.');
        } catch (Throwable $e) {
            $this->error('[HIBA] Titkositas: '.$this->describe($e));

            return 1;
        }

        return 0;
    }

    private function sectionFilesystem(): int
    {
        $this->header('3. Fajlrendszer es jogosultsagok');

        $failures = 0;

        $paths = [
            storage_path(),
            storage_path('logs'),
            storage_path('framework'),
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            base_path('bootstrap/cache'),
        ];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                $this->error('[HIBA] Hianyzo konyvtar: '.$path);
                $failures++;

                continue;
            }

            if (! is_writable($path)) {
                $this->error('[HIBA] Nem irhato: '.$path.' (jogosultsag: '.substr(sprintf('%o', fileperms($path)), -4).')');
                $failures++;

                continue;
            }

            $this->line('  [OK] '.$path.' ('.substr(sprintf('%o', fileperms($path)), -4).')');
        }

        $probe = storage_path('framework/views/.fakt-diagnose-write-test');
        if (@file_put_contents($probe, 'ok') === false) {
            $this->error('[HIBA] Nem sikerult irni ide: '.dirname($probe));
            $failures++;
        } else {
            @unlink($probe);
            $this->line('  [OK] Iras teszt sikeres a compiled views konyvtarban.');
        }

        $manifest = public_path('build/manifest.json');
        if (is_file($manifest)) {
            $this->line('  [OK] Vite manifest: '.$manifest);
        } else {
            $this->warn('[FIGYELEM] Hianyzik a Vite manifest: '.$manifest.' - az oldal betolt, de JavaScript nelkul.');
        }

        $this->kv('futtato felhasznalo', function_exists('posix_geteuid') && function_exists('posix_getpwuid')
            ? (string) (posix_getpwuid(posix_geteuid())['name'] ?? 'ismeretlen')
            : (string) (getenv('USER') ?: 'ismeretlen'));

        return $failures;
    }

    private function sectionDatabase(): int
    {
        $this->header('4. Adatbazis');

        try {
            DB::connection()->getPdo();
            $this->line('  [OK] Kapcsolat letrejott: '.config('database.default'));
        } catch (Throwable $e) {
            $this->error('[HIBA] Adatbazis kapcsolat: '.$this->describe($e));

            return 1;
        }

        $failures = 0;

        $tables = [
            (string) config('session.table', 'sessions'),
            (string) config('cache.stores.database.table', 'cache'),
            'users',
            'jobs',
        ];

        foreach (array_unique($tables) as $table) {
            try {
                if (Schema::hasTable($table)) {
                    $this->line('  [OK] tabla: '.$table.' ('.DB::table($table)->count().' sor)');
                } else {
                    $this->error('[HIBA] Hianyzo tabla: '.$table);
                    $failures++;
                }
            } catch (Throwable $e) {
                $this->error('[HIBA] A(z) '.$table.' tabla nem olvashato: '.$this->describe($e));
                $failures++;
            }
        }

        // A session driver minden keresnel ir. Ha a runtime DB-user csak SELECT jogot kapott,
        // az artisan parancsok hibatlanok maradnak, de minden bongeszo keres 500-at ad.
        $cacheTable = (string) config('cache.stores.database.table', 'cache');
        $probeKey = 'fakt-diagnose-'.bin2hex(random_bytes(4));

        try {
            DB::table($cacheTable)->insert([
                'key' => $probeKey,
                'value' => 'probe',
                'expiration' => time() + 60,
            ]);
            DB::table($cacheTable)->where('key', $probeKey)->delete();
            $this->line('  [OK] INSERT/DELETE jogosultsag rendben a(z) '.$cacheTable.' tablan.');
        } catch (Throwable $e) {
            $this->error('[HIBA] A DB-usernek nincs iras joga a(z) '.$cacheTable.' tablan: '.$this->describe($e));
            $this->line('        Session es cache iras nelkul minden HTTP keres 500-at ad.');
            $failures++;
        }

        return $failures;
    }

    private function sectionLogging(): int
    {
        $this->header('5. Naplozas');

        $failures = 0;

        foreach (['stack', 'security'] as $channel) {
            try {
                Log::channel($channel)->warning('fakt:diagnose naplo iras teszt');
                $this->line('  [OK] naplo csatorna irhato: '.$channel);
            } catch (Throwable $e) {
                $this->error('[HIBA] A(z) '.$channel.' naplo csatorna nem irhato: '.$this->describe($e));
                $this->line('        Ilyenkor a Laravel naploban NEM lesz nyoma a hibaknak.');
                $failures++;
            }
        }

        return $failures;
    }

    private function sectionHttp(): int
    {
        $this->header('6. Valodi HTTP keres ujrajatszasa');

        $path = '/'.ltrim((string) $this->option('path'), '/');
        $host = (string) ($this->option('host') ?: parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost');
        $url = 'https://'.$host.$path;

        $this->kv('kert URL', $url);

        $original = app(ExceptionHandler::class);
        app()->instance(ExceptionHandler::class, new DiagnoseExceptionHandler($original));

        $response = null;

        try {
            /** @var \Illuminate\Contracts\Http\Kernel $kernel */
            $kernel = app(HttpKernelContract::class);
            $response = $kernel->handle(Request::create($url, 'GET'));
            $status = $response->getStatusCode();
        } catch (Throwable $e) {
            self::$captured[] = $e;
            $status = 0;
        } finally {
            app()->instance(ExceptionHandler::class, $original);
        }

        $this->kv('HTTP statusz', $status === 0 ? 'kivetel a kernelbol' : (string) $status);

        if ($response instanceof Response) {
            $this->kv('Content-Type', (string) $response->headers->get('Content-Type'));
            $this->kv('valasz hossza', strlen((string) $response->getContent()).' bajt');
        }

        if (self::$captured === []) {
            if ($status >= 200 && $status < 400) {
                $this->line('  [OK] Az oldal a szerveren belul hibatlanul legeneralodott.');
                $this->line('        Ha a bongeszo megis 500-at kap, a hiba nem a PHP kodban van, hanem');
                $this->line('        az Apache/.htaccess retegben vagy a public mappa index.php utvonalaban.');

                return 0;
            }

            $this->error('[HIBA] A valasz statusza '.$status.', de nem keletkezett kivetel.');

            return 1;
        }

        $failures = 0;
        $traceLines = max(0, (int) $this->option('trace'));

        foreach (self::$captured as $index => $e) {
            $failures++;
            $this->newLine();
            $this->error('[HIBA] Kivetel #'.($index + 1));

            $current = $e;
            $level = 0;

            while ($current instanceof Throwable) {
                $this->line(($level === 0 ? '  ' : '  -> elozmeny: ').get_class($current));
                $this->line('     uzenet: '.$current->getMessage());
                $this->line('     hely  : '.$current->getFile().':'.$current->getLine());

                if ($level === 0 && $traceLines > 0) {
                    $this->line('     stack :');
                    foreach (array_slice(explode("\n", $current->getTraceAsString()), 0, $traceLines) as $line) {
                        $this->line('       '.$line);
                    }
                }

                $current = $current->getPrevious();
                $level++;
            }
        }

        return $failures;
    }

    /**
     * Anything that breaks before Laravel boots - a wrong PHP handler, a failing
     * autoloader, Composer's platform check - never reaches the Laravel log.
     * The hardened public index.php records those here instead.
     */
    private function tailBootstrapLog(): void
    {
        $this->header('7. Bootstrap hibanaplo (a Laravel indulasa elott)');

        $file = storage_path('logs/bootstrap-error.log');

        if (! is_file($file)) {
            $this->line('  [OK] Nincs bootstrap-error.log, tehat az index.php eddig mindig eljutott a Laravelig.');

            return;
        }

        $this->kv('fajl', $file.' ('.date('Y-m-d H:i:s', (int) filemtime($file)).')');
        $this->error('[HIBA] A weboldal a Laravel elindulasa elott hibazott. Utolso bejegyzesek:');

        $lines = @file($file, FILE_IGNORE_NEW_LINES) ?: [];

        foreach (array_slice($lines, -max(1, (int) $this->option('log-lines'))) as $line) {
            $this->line('  '.$line);
        }
    }

    private function tailLaravelLog(): void
    {
        $this->header('8. Legfrissebb Laravel naplo');

        $files = glob(storage_path('logs').'/laravel*.log') ?: [];

        if ($files === []) {
            $this->warn('[FIGYELEM] Nincs laravel*.log fajl a storage/logs mappaban.');

            return;
        }

        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        $file = $files[0];

        $this->kv('fajl', $file.' ('.date('Y-m-d H:i:s', (int) filemtime($file)).')');

        $lines = @file($file, FILE_IGNORE_NEW_LINES) ?: [];

        foreach (array_slice($lines, -max(1, (int) $this->option('log-lines'))) as $line) {
            $this->line('  '.$line);
        }
    }

    private function describe(Throwable $e): string
    {
        return get_class($e).': '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine();
    }

    private function header(string $title): void
    {
        $this->newLine();
        $this->line('-- '.$title.' '.str_repeat('-', max(0, 68 - strlen($title))));
    }

    private function kv(string $key, string $value): void
    {
        $this->line('  '.str_pad($key, 24).': '.$value);
    }
}

/**
 * Captures every exception the HTTP kernel swallows, then hands it back to the
 * real handler so behaviour stays identical.
 */
class DiagnoseExceptionHandler implements ExceptionHandler
{
    public function __construct(private readonly ExceptionHandler $inner) {}

    public function report(Throwable $e)
    {
        Diagnose::$captured[] = $e;

        try {
            $this->inner->report($e);
        } catch (Throwable) {
            // Logging itself may be broken; the exception is already captured.
        }
    }

    public function shouldReport(Throwable $e)
    {
        return true;
    }

    public function render($request, Throwable $e)
    {
        if (! in_array($e, Diagnose::$captured, true)) {
            Diagnose::$captured[] = $e;
        }

        try {
            return $this->inner->render($request, $e);
        } catch (Throwable) {
            return new Response('diagnose', 500);
        }
    }

    public function renderForConsole($output, Throwable $e)
    {
        $this->inner->renderForConsole($output, $e);
    }

    /** @param array<int, mixed> $parameters */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->inner->{$method}(...$parameters);
    }
}
