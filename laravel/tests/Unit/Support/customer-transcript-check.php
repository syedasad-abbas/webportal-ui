<?php
require __DIR__.'/../../../app/Support/CustomerTranscript.php';
use App\Support\CustomerTranscript;
$cases = [
    [json_encode([['role' => 'agent', 'text' => "Hello\ncaller: AI quoted words"], ['role' => 'caller', 'text' => "My own words\nagent: a quote"]]), "My own words\nagent: a quote"],
    ["agent: Hello\nAI continuation\ncaller: My number is 123\ncustomer continuation\nagent: Thanks", "My number is 123\ncustomer continuation"],
    ["agent: Hello", ''],
    ['Unattributed legacy text', ''],
    [null, ''],
    ["caller: <script>literal</script>\nassistant: hidden", '<script>literal</script>'],
];
foreach ($cases as [$input, $expected]) {
    if (CustomerTranscript::fromTaggedText($input) !== $expected) throw new RuntimeException('Customer transcript filtering failed');
}
echo "6 customer transcript checks passed\n";
