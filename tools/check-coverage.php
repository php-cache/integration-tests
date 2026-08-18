<?php

declare(strict_types=1);

$arguments = $_SERVER['argv'] ?? [];
if (!is_array($arguments) || !array_is_list($arguments) || 3 !== count($arguments) || !is_string($arguments[1]) || !is_string($arguments[2]) || !is_numeric($arguments[2])) {
    fwrite(STDERR, "Usage: php tools/check-coverage.php <clover.xml> <minimum-percent>\n");

    exit(2);
}

$document = new DOMDocument();
if (!$document->load($arguments[1])) {
    fwrite(STDERR, sprintf("Could not read coverage report %s.\n", $arguments[1]));

    exit(2);
}

$metricNodes = (new DOMXPath($document))->query('/coverage/project/metrics');
$metrics = false === $metricNodes ? null : $metricNodes->item(0);
if (!$metrics instanceof DOMElement) {
    fwrite(STDERR, "The coverage report does not contain project metrics.\n");

    exit(2);
}

$statements = (int) $metrics->getAttribute('statements');
$coveredStatements = (int) $metrics->getAttribute('coveredstatements');
$minimum = (float) $arguments[2];
if (0 === $statements) {
    fwrite(STDERR, "The coverage report does not contain executable statements.\n");

    exit(2);
}

$percentage = 100 * $coveredStatements / $statements;
printf(
    "Line coverage: %.2f%% (%d/%d); required: %.2f%%.\n",
    $percentage,
    $coveredStatements,
    $statements,
    $minimum,
);

exit($percentage >= $minimum ? 0 : 1);
