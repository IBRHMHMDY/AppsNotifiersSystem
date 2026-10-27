<?php

// tests/Unit/ArchitectureTest.php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

class ArchitectureTest extends TestCase
{
    public function test_all_dtos_are_readonly(): void
    {
        $dtoFiles = glob(__DIR__.'/../../app/DTOs/*.php') ?: [];

        $this->assertNotEmpty($dtoFiles);

        foreach ($dtoFiles as $file) {
            $className = 'App\\DTOs\\'.basename($file, '.php');
            $reflection = new ReflectionClass($className);

            $this->assertTrue(
                $reflection->isReadOnly(),
                "DTO class [{$className}] must be declared as readonly."
            );
        }
    }

    public function test_core_layers_declare_strict_types_and_contain_no_debug_calls(): void
    {
        $directories = [
            __DIR__.'/../../app/Actions',
            __DIR__.'/../../app/DTOs',
            __DIR__.'/../../app/Services',
            __DIR__.'/../../app/Jobs',
        ];

        foreach ($directories as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $content = (string) file_get_contents($file->getPathname());

                $this->assertStringContainsString(
                    'declare(strict_types=1);',
                    $content,
                    "File [{$file->getPathname()}] is missing declare(strict_types=1);"
                );

                $this->assertDoesNotMatchRegularExpression(
                    '/\b(dd|dump|ray)\s*\(/',
                    $content,
                    "File [{$file->getPathname()}] contains forgotten debugging calls."
                );
            }
        }
    }
}
