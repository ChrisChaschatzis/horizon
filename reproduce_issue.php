<?php
require_once 'app/src/Importer.php';

// Mock PDO
class MockPDO extends PDO {
    public function __construct() {}
}

// Reflection to test private method
$importer = new Importer(new MockPDO());
$reflection = new ReflectionClass($importer);
$method = $reflection->getMethod('parseBoolean');
$method->setAccessible(true);

$testCases = [
    'FALSE' => 0,
    'TRUE' => 1,
    'False' => 0,
    'True' => 1,
    '0' => 0,
    '1' => 1,
    0 => 0,
    1 => 1,
    true => 1,
    false => 0,
    'YES' => 1,
    'NO' => 0,
    '' => 0,
    null => 0
];

echo "Testing parseBoolean...\n";
foreach ($testCases as $input => $expected) {
    $result = $method->invoke($importer, $input);
    $status = ($result === $expected) ? "PASS" : "FAIL (Expected $expected, got " . var_export($result, true) . ")";
    echo "Input: " . var_export($input, true) . " -> $status\n";
}
