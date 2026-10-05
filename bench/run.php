<?php

/*
 * Nucleon benchmark runner. Each iteration runs bench/scenario.php in a new
 * PHP process (OPcache enabled with a file cache, so that compiled code is
 * reused between processes as it would be between PHP-FPM requests).
 *
 *   php bench/run.php [options]
 *
 *   --autoload=PATH      vendor/autoload.php of the Nucleon version to measure
 *                        (default: ./vendor/autoload.php)
 *   --scenarios=a,b      scenarios to run (default: all)
 *   --iterations=N       measured iterations per scenario (default: 200)
 *   --warmup=N           discarded iterations per scenario (default: 20)
 *   --out=FILE           write the results as JSON
 *   --compare=FILE       compare with a previous JSON result (e.g. the 1.3 baseline)
 *
 * Must stay compatible with PHP 7.3: the same file measures Nucleon 1.3.
 */

$scenarios = ['boot-http', 'boot-cli', 'boot-micro', 'http', 'micro', 'cli', 'service'];

$options = getopt('', ['autoload:', 'scenarios:', 'iterations:', 'warmup:', 'out:', 'compare:']);

$autoload = isset($options['autoload']) ? realpath($options['autoload']) : realpath(__DIR__ . '/../vendor/autoload.php');
$iterations = isset($options['iterations']) ? (int) $options['iterations'] : 200;
$warmup = isset($options['warmup']) ? (int) $options['warmup'] : 20;
if (isset($options['scenarios'])) {
    $scenarios = explode(',', $options['scenarios']);
}

if ($autoload === false) {
    fwrite(STDERR, "Autoload file not found.\n");
    exit(1);
}

$opcacheDir = sys_get_temp_dir() . '/nucleon-bench-opcache-' . md5($autoload . PHP_VERSION);
if (!is_dir($opcacheDir)) {
    mkdir($opcacheDir, 0777, true);
}

$phpArgs = [
    '-d', 'opcache.enable_cli=1',
    '-d', 'opcache.file_cache=' . $opcacheDir,
    '-d', 'opcache.validate_timestamps=0',
    '-d', 'error_reporting=' . (E_ALL & ~E_DEPRECATED),
];

$results = [
    'environment' => [
        'php' => PHP_VERSION,
        'phalcon' => phalconVersion(),
        'autoload' => $autoload,
        'iterations' => $iterations,
        'date' => date(DATE_ATOM),
    ],
    'scenarios' => [],
];

// Scenarios are interleaved (round-robin) so that machine-wide drift
// (thermal throttling, other processes) affects all of them equally.
for ($i = 0; $i < $warmup; $i++) {
    foreach ($scenarios as $scenario) {
        runScenario($phpArgs, $scenario, $autoload);
    }
}

$times = array_fill_keys($scenarios, []);
$memories = array_fill_keys($scenarios, []);
for ($i = 0; $i < $iterations; $i++) {
    foreach ($scenarios as $scenario) {
        $measure = runScenario($phpArgs, $scenario, $autoload);
        $times[$scenario][] = $measure['time_ns'] / 1e3;
        $memories[$scenario][] = $measure['memory_peak'];
    }
}

foreach ($scenarios as $scenario) {
    $results['scenarios'][$scenario] = [
        'time_us' => statistics($times[$scenario]),
        'memory_peak' => statistics($memories[$scenario]),
    ];
}

$baseline = null;
if (isset($options['compare'])) {
    $baseline = json_decode(file_get_contents($options['compare']), true);
}

printReport($results, $baseline);

if (isset($options['out'])) {
    file_put_contents($options['out'], json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
}

function runScenario(array $phpArgs, $scenario, $autoload)
{
    $command = escapeshellarg(PHP_BINARY);
    foreach ($phpArgs as $arg) {
        $command .= ' ' . escapeshellarg($arg);
    }
    $command .= ' ' . escapeshellarg(__DIR__ . '/scenario.php')
        . ' ' . escapeshellarg($scenario)
        . ' ' . escapeshellarg($autoload)
        . ' 2>&1';

    exec($command, $output, $code);

    foreach (array_reverse($output) as $line) {
        if (strpos($line, '@@BENCH@@') === 0) {
            return json_decode(substr($line, 9), true);
        }
    }

    fwrite(STDERR, "Scenario \"$scenario\" failed (exit $code):\n" . implode(PHP_EOL, $output) . PHP_EOL);
    exit(1);
}

function statistics(array $values)
{
    sort($values);
    $count = count($values);
    $mean = array_sum($values) / $count;
    $variance = 0.0;
    foreach ($values as $value) {
        $variance += ($value - $mean) ** 2;
    }

    return [
        'median' => percentile($values, 50),
        'p95' => percentile($values, 95),
        'mean' => $mean,
        'stddev' => sqrt($variance / $count),
        'min' => $values[0],
        'max' => $values[$count - 1],
    ];
}

function percentile(array $sorted, $percent)
{
    $index = ($percent / 100) * (count($sorted) - 1);
    $lower = (int) floor($index);
    $upper = (int) ceil($index);

    return $sorted[$lower] + ($sorted[$upper] - $sorted[$lower]) * ($index - $lower);
}

function phalconVersion()
{
    if (class_exists('Phalcon\Support\Version')) {
        return (new Phalcon\Support\Version())->get();
    }
    if (class_exists('Phalcon\Version')) {
        return Phalcon\Version::get();
    }

    return 'none';
}

function printReport(array $results, $baseline)
{
    $env = $results['environment'];
    printf("PHP %s, Phalcon %s, %d iterations\n\n", $env['php'], $env['phalcon'], $env['iterations']);

    $header = sprintf('%-12s %12s %12s %12s', 'scenario', 'median (µs)', 'p95 (µs)', 'mem (KiB)');
    if ($baseline) {
        $header .= sprintf(' %10s %10s', 'Δ time', 'Δ mem');
    }
    echo $header, PHP_EOL, str_repeat('-', strlen($header)), PHP_EOL;

    foreach ($results['scenarios'] as $name => $data) {
        $line = sprintf(
            '%-12s %12.1f %12.1f %12.1f',
            $name,
            $data['time_us']['median'],
            $data['time_us']['p95'],
            $data['memory_peak']['median'] / 1024
        );
        if ($baseline && isset($baseline['scenarios'][$name])) {
            $base = $baseline['scenarios'][$name];
            $line .= sprintf(
                ' %+9.1f%% %+9.1f%%',
                ($data['time_us']['median'] / $base['time_us']['median'] - 1) * 100,
                ($data['memory_peak']['median'] / $base['memory_peak']['median'] - 1) * 100
            );
        }
        echo $line, PHP_EOL;
    }
}
