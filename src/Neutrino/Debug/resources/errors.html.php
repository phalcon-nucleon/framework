<?php

declare(strict_types=1);

/**
 * Debug error page, rendered by Neutrino\Debug\Debugger::renderErrorPage().
 *
 * @var Neutrino\Error\Error                          $error
 * @var list<Throwable>                               $exceptions
 * @var list<Neutrino\Error\Error>                    $phpErrors
 * @var array{php: string, phalcon: string, nucleon: string} $build
 */

use Neutrino\Debug\Debugger;
use Neutrino\Debug\Highlight;
use Neutrino\Error\Helper;
use Phalcon\Logger\Enum;

$e = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE);

$location = static function (string $file, int $line, bool $open = false) use ($e): string {
    $where = '<span class="file">' . $e(Debugger::relativePath($file)) . '</span>' . ($line > 0 ? ' <span class="line">line ' . $line . '</span>' : '');
    $code = $line > 0 ? Highlight::fileFragment($file, $line) : '';

    if ($code === '') {
        return '<div class="where">' . $where . '</div>';
    }

    return '<details class="where"' . ($open ? ' open' : '') . '><summary>' . $where . '</summary><pre class="code">' . $code . '</pre></details>';
};

$severity = static fn(int $level): string => match ($level) {
    Enum::WARNING => 'warning',
    Enum::NOTICE  => 'notice',
    Enum::INFO, Enum::DEBUG => 'info',
    default       => 'error',
};

$title = $exceptions === [] ? $error->typeStr : $exceptions[0]::class;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= $e($title) ?></title>
<style>
*{box-sizing:border-box}
body{margin:0;font:14px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:#1f2328;background:#f6f8fa}
main{max-width:1200px;margin:0 auto;padding:24px 16px}
h1{margin:0 0 4px;font-size:20px;color:#cf222e;word-break:break-all}
h2{margin:32px 0 12px;font-size:16px}
.card{background:#fff;border:1px solid #d0d7de;border-radius:6px;padding:16px;margin-bottom:16px}
.meta{color:#656d76;font-size:12px}
.message{margin:8px 0;font:15px/1.5 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;white-space:pre-wrap;word-break:break-word}
.where{margin:4px 0;font-size:13px}
.where summary{cursor:pointer}
.file{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;word-break:break-all}
.line{color:#656d76}
pre.code{margin:8px 0;padding:8px 0;overflow-x:auto;font:12px/1.6 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;background:#f6f8fa;border-radius:6px}
ol.trace{margin:12px 0 0;padding:0 0 0 2.5em;font-size:13px}
ol.trace li{padding:6px 0;border-top:1px solid #eaeef2}
.func{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;word-break:break-all}
.php-error{border-left:4px solid #cf222e}
.php-error.warning{border-left-color:#bf8700}
.php-error.notice{border-left-color:#0969da}
.php-error.info{border-left-color:#8c959f}
footer{margin-top:32px;color:#656d76;font-size:12px}
<?= Highlight::css() ?>

</style>
</head>
<body>
<main>
<?php if ($exceptions === []) : ?>
  <section class="card">
    <h1><?= $e($error->typeStr) ?></h1>
    <div class="meta">Code <?= $e($error->code) ?></div>
    <div class="message"><?= $e($error->message === '' ? 'No message' : $error->message) ?></div>
    <?= $location($error->file, $error->line, true) ?>
  </section>
<?php else : ?>
  <?php foreach ($exceptions as $index => $exception) : ?>
  <section class="card">
    <?php if ($index > 0) : ?><div class="meta">Previous exception #<?= $index ?></div><?php endif ?>
    <h1><?= $e($exception::class) ?></h1>
    <div class="meta">Code <?= $e($exception->getCode()) ?></div>
    <div class="message"><?= $e($exception->getMessage() === '' ? 'No message' : $exception->getMessage()) ?></div>
    <?= $location($exception->getFile(), $exception->getLine(), $index === 0) ?>
    <?php $traces = Helper::formatExceptionTrace($exception) ?>
    <?php if ($traces !== []) : ?>
    <ol class="trace" start="0">
      <?php foreach ($traces as $trace) : ?>
      <li>
        <div class="func"><?= Highlight::html($trace['func'], 'php') ?></div>
        <?= isset($trace['file']) ? $location($trace['file'], $trace['line'] ?? 0) : '<div class="where line">[internal function]</div>' ?>
      </li>
      <?php endforeach ?>
    </ol>
    <?php endif ?>
  </section>
  <?php endforeach ?>
<?php endif ?>

<?php if ($phpErrors !== []) : ?>
  <h2>PHP errors (<?= count($phpErrors) ?>)</h2>
  <?php foreach ($phpErrors as $phpError) : ?>
  <section class="card php-error <?= $severity($phpError->logLvl) ?>">
    <strong><?= $e($phpError->typeStr) ?></strong>
    <div class="message"><?= $e($phpError->message === '' ? 'No message' : $phpError->message) ?></div>
    <?= $location($phpError->file, $phpError->line) ?>
  </section>
  <?php endforeach ?>
<?php endif ?>

  <footer>PHP <?= $e($build['php']) ?> · Phalcon <?= $e($build['phalcon']) ?> · Nucleon <?= $e($build['nucleon']) ?></footer>
</main>
</body>
</html>
