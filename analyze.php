<?php

$dir = $argv[1];

$results = [
    'public' => 0,
    'var' => 0,
    'varByPath' => [],
];

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($it as $file) {
    if (!preg_match('/\.php$/', $file)) {
        continue;
    }

    $subPath = substr($file, strlen($dir));

    $code = file_get_contents($file);
    $tokens = token_get_all($code);
    $tokenCount = count($tokens);
    for ($i = 0; $i < $tokenCount; $i++) {
        $token = $tokens[$i];
        if (is_array($token)) {
            if ($token[0] === T_PUBLIC) {
                // Is this for a variable?
                // Advance through any whitespace or static tokens until we hit a variable or something else
                for ($i++; $i < $tokenCount; $i++) {
                    $token = $tokens[$i];
                    if (is_array($token)) {
                        if ($token[0] === T_WHITESPACE || $token[0] === T_STATIC) {
                            continue;
                        }
                        if ($token[0] === T_VARIABLE) {
                            $results['public']++;
                        }
                    }
                    break;
                }
            } elseif ($token[0] === T_VAR) {
                $results['var']++;
                $results['varByPath'][$subPath]++;
            }
        }
    }
}

unset($it);

echo json_encode($results);
exit(0);
