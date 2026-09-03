<?php

$dir = __DIR__ . '/resources/views/livewire';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

$replacements = [
    '<flux:table.head>'    => '<flux:table.columns>',
    '</flux:table.head>'   => '</flux:table.columns>',
    '<flux:table.body>'    => '<flux:table.rows>',
    '</flux:table.body>'   => '</flux:table.rows>',
    '<flux:table.cell heading>' => '<flux:table.column>',
];

$count = 0;
foreach ($files as $file) {
    if ($file->getExtension() !== 'php') continue;
    
    $content = file_get_contents($file->getPathname());
    $new = str_replace(array_keys($replacements), array_values($replacements), $content);
    
    if ($new !== $content) {
        file_put_contents($file->getPathname(), $new);
        echo "Fixed: " . $file->getFilename() . "\n";
        $count++;
    }
}

echo "\nDone. Fixed $count files.\n";
