<?php

declare(strict_types=1);

if ($argc !== 2) {
    fwrite(STDERR, "Usage: php scripts/check-php-coverage.php <clover.xml>\n");
    exit(2);
}

$coverage = simplexml_load_file($argv[1]);
if ($coverage === false) {
    fwrite(STDERR, "Unable to read coverage report: {$argv[1]}\n");
    exit(2);
}

$failures = [];
foreach ($coverage->xpath('//project/package/file | //project/file') ?: [] as $file) {
    $metrics = $file->metrics;
    $path = (string) $file['name'];

    foreach ([
        'statements' => 'coveredstatements',
        'conditionals' => 'coveredconditionals',
    ] as $total => $covered) {
        if ((int) $metrics[$total] !== (int) $metrics[$covered]) {
            $failures[] = sprintf(
                '%s: %s %d/%d',
                $path,
                $total,
                (int) $metrics[$covered],
                (int) $metrics[$total],
            );
        }
    }
}

if ($failures !== []) {
    fwrite(STDERR, "Coverage must be 100% for statements and branches:\n");
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "PHP statements and branches: 100%\n");
