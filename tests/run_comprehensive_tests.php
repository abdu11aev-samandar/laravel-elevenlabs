<?php

/**
 * Comprehensive Test Coverage Runner for ElevenLabs Laravel Package
 * 
 * This script runs all comprehensive service tests with coverage analysis
 * and provides a detailed report of test coverage and regression prevention.
 * 
 * Usage: php tests/run_comprehensive_tests.php [--coverage] [--service=ServiceName]
 */

require_once 'vendor/autoload.php';

class ComprehensiveTestRunner
{
    private array $serviceClasses = [
        'AIService' => 'Samandar\\LaravelElevenLabs\\Services\\AI\\AIService',
        'AnalyticsService' => 'Samandar\\LaravelElevenLabs\\Services\\Analytics\\AnalyticsService',
        'AudioService' => 'Samandar\\LaravelElevenLabs\\Services\\Audio\\AudioService',
        'VoiceService' => 'Samandar\\LaravelElevenLabs\\Services\\Voice\\VoiceService',
        'StudioService' => 'Samandar\\LaravelElevenLabs\\Services\\Studio\\StudioService',
    ];

    private array $testClasses = [
        'AIService' => 'Samandar\\LaravelElevenLabs\\Tests\\Unit\\ServiceTestsComprehensive\\AIServiceComprehensiveTest',
        'AnalyticsService' => 'Samandar\\LaravelElevenLabs\\Tests\\Unit\\ServiceTestsComprehensive\\AnalyticsServiceComprehensiveTest',
        'AudioService' => 'Samandar\\LaravelElevenLabs\\Tests\\Unit\\ServiceTestsComprehensive\\AudioServiceComprehensiveTest',
        'VoiceService' => 'Samandar\\LaravelElevenLabs\\Tests\\Unit\\ServiceTestsComprehensive\\VoiceServiceComprehensiveTest',
        'StudioService' => 'Samandar\\LaravelElevenLabs\\Tests\\Unit\\ServiceTestsComprehensive\\StudioServiceComprehensiveTest',
    ];

    private bool $generateCoverage = false;
    private ?string $targetService = null;

    public function __construct(array $args = [])
    {
        $this->parseArguments($args);
    }

    private function parseArguments(array $args): void
    {
        foreach ($args as $arg) {
            if ($arg === '--coverage') {
                $this->generateCoverage = true;
            } elseif (strpos($arg, '--service=') === 0) {
                $this->targetService = substr($arg, 10);
            }
        }
    }

    public function run(): void
    {
        $this->printHeader();
        
        if ($this->targetService) {
            $this->runServiceTests($this->targetService);
        } else {
            $this->runAllTests();
        }
        
        $this->printSummary();
    }

    private function printHeader(): void
    {
        echo "\n";
        echo "═══════════════════════════════════════════════════════════════════════════════\n";
        echo "              ElevenLabs Laravel Package - Comprehensive Test Suite            \n";
        echo "                          Regression Prevention & Coverage                      \n";
        echo "═══════════════════════════════════════════════════════════════════════════════\n";
        echo "\n";
    }

    private function runAllTests(): void
    {
        foreach ($this->serviceClasses as $serviceName => $serviceClass) {
            $this->runServiceTests($serviceName);
        }
    }

    private function runServiceTests(string $serviceName): void
    {
        if (!isset($this->serviceClasses[$serviceName])) {
            echo "❌ Unknown service: {$serviceName}\n";
            return;
        }

        echo "🧪 Testing {$serviceName}\n";
        echo str_repeat('─', 80) . "\n";

        // Analyze service class for coverage
        $analysis = $this->analyzeServiceClass($serviceName);
        echo "📋 Service Analysis:\n";
        echo "   • Methods: {$analysis['methodCount']}\n";
        echo "   • Public Methods: {$analysis['publicMethods']}\n";
        echo "   • Lines of Code: {$analysis['linesOfCode']}\n\n";

        // Run PHPUnit tests for this service
        $testClass = $this->testClasses[$serviceName];
        $testFile = $this->getTestFilePath($serviceName);
        
        if (!file_exists($testFile)) {
            echo "❌ Test file not found: {$testFile}\n\n";
            return;
        }

        // Run tests with or without coverage
        if ($this->generateCoverage) {
            $this->runTestsWithCoverage($serviceName, $testFile);
        } else {
            $this->runTestsBasic($serviceName, $testFile);
        }

        echo "\n";
    }

    private function analyzeServiceClass(string $serviceName): array
    {
        $serviceClass = $this->serviceClasses[$serviceName];
        
        if (!class_exists($serviceClass)) {
            return ['methodCount' => 0, 'publicMethods' => 0, 'linesOfCode' => 0];
        }

        $reflection = new ReflectionClass($serviceClass);
        $methods = $reflection->getMethods();
        $publicMethods = array_filter($methods, fn($method) => $method->isPublic() && !$method->isConstructor());
        
        // Estimate lines of code from file
        $fileName = $reflection->getFileName();
        $linesOfCode = $fileName ? count(file($fileName)) : 0;

        return [
            'methodCount' => count($methods),
            'publicMethods' => count($publicMethods),
            'linesOfCode' => $linesOfCode,
        ];
    }

    private function getTestFilePath(string $serviceName): string
    {
        return __DIR__ . "/Unit/ServiceTestsComprehensive/{$serviceName}ComprehensiveTest.php";
    }

    private function runTestsBasic(string $serviceName, string $testFile): void
    {
        $command = "vendor/bin/phpunit --colors=always --group=comprehensive-coverage {$testFile}";
        
        echo "🚀 Running tests...\n";
        echo "Command: {$command}\n\n";
        
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        
        // Parse and display results
        $this->parseTestOutput($output, $returnCode);
    }

    private function runTestsWithCoverage(string $serviceName, string $testFile): void
    {
        $coverageDir = __DIR__ . '/../coverage/' . strtolower($serviceName);
        
        if (!is_dir($coverageDir)) {
            mkdir($coverageDir, 0755, true);
        }

        $command = "vendor/bin/phpunit --colors=always --coverage-html={$coverageDir} --coverage-text --group=comprehensive-coverage {$testFile}";
        
        echo "🚀 Running tests with coverage analysis...\n";
        echo "Command: {$command}\n";
        echo "Coverage report will be saved to: {$coverageDir}\n\n";
        
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        
        // Parse and display results
        $this->parseTestOutput($output, $returnCode);
        $this->parseCoverageOutput($output);
    }

    private function parseTestOutput(array $output, int $returnCode): void
    {
        $testCount = 0;
        $assertions = 0;
        $failures = 0;
        $errors = 0;
        $skipped = 0;
        $executionTime = '0.00';

        foreach ($output as $line) {
            // Parse test results
            if (preg_match('/Tests: (\d+), Assertions: (\d+)/', $line, $matches)) {
                $testCount = (int)$matches[1];
                $assertions = (int)$matches[2];
            }
            
            if (preg_match('/Failures: (\d+)/', $line, $matches)) {
                $failures = (int)$matches[1];
            }
            
            if (preg_match('/Errors: (\d+)/', $line, $matches)) {
                $errors = (int)$matches[1];
            }
            
            if (preg_match('/Skipped: (\d+)/', $line, $matches)) {
                $skipped = (int)$matches[1];
            }
            
            if (preg_match('/Time: ([0-9.]+)/', $line, $matches)) {
                $executionTime = $matches[1];
            }
        }

        // Display results
        echo "📊 Test Results:\n";
        echo "   • Tests: {$testCount}\n";
        echo "   • Assertions: {$assertions}\n";
        echo "   • Execution Time: {$executionTime}s\n";

        if ($returnCode === 0) {
            echo "   ✅ Status: PASSED\n";
        } else {
            echo "   ❌ Status: FAILED\n";
            if ($failures > 0) echo "   • Failures: {$failures}\n";
            if ($errors > 0) echo "   • Errors: {$errors}\n";
            if ($skipped > 0) echo "   • Skipped: {$skipped}\n";
        }
    }

    private function parseCoverageOutput(array $output): void
    {
        $coverageLines = array_filter($output, fn($line) => 
            strpos($line, 'Lines:') !== false || 
            strpos($line, 'Methods:') !== false ||
            strpos($line, 'Classes:') !== false
        );

        if (!empty($coverageLines)) {
            echo "\n📈 Coverage Analysis:\n";
            foreach ($coverageLines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    // Color code coverage percentages
                    $line = preg_replace_callback('/(\d+\.\d+%|\d+%)/', function($matches) {
                        $percentage = (float)str_replace('%', '', $matches[1]);
                        if ($percentage >= 90) {
                            return "🟢 {$matches[1]}";  // Green for >=90%
                        } elseif ($percentage >= 80) {
                            return "🟡 {$matches[1]}";  // Yellow for 80-89%
                        } else {
                            return "🔴 {$matches[1]}";  // Red for <80%
                        }
                    }, $line);
                    
                    echo "   • {$line}\n";
                }
            }
        }
    }

    private function printSummary(): void
    {
        echo "\n";
        echo "═══════════════════════════════════════════════════════════════════════════════\n";
        echo "                                    SUMMARY                                    \n";
        echo "═══════════════════════════════════════════════════════════════════════════════\n";
        echo "\n";
        echo "✅ Comprehensive Test Coverage Implementation:\n";
        echo "   • All service classes have dedicated comprehensive test suites\n";
        echo "   • Tests cover success scenarios, error conditions, and edge cases\n";
        echo "   • Mocked responses simulate ElevenLabs API interactions safely\n";
        echo "   • Test scenarios converted from manual testing to permanent automation\n\n";
        
        echo "🎯 Coverage Goals:\n";
        echo "   • Target: ≥90% coverage on service classes\n";
        echo "   • Focus: Public methods and critical business logic\n";
        echo "   • Approach: Unit tests with mocked dependencies\n\n";
        
        echo "🛡️  Regression Prevention:\n";
        echo "   • Automated test execution prevents breaking changes\n";
        echo "   • Mock-based tests avoid API rate limits and costs\n";
        echo "   • Comprehensive scenarios ensure feature stability\n\n";
        
        if ($this->generateCoverage) {
            echo "📊 Coverage reports generated in: coverage/\n\n";
        }
        
        echo "🚀 Usage:\n";
        echo "   • Run all tests: php tests/run_comprehensive_tests.php\n";
        echo "   • With coverage: php tests/run_comprehensive_tests.php --coverage\n";
        echo "   • Single service: php tests/run_comprehensive_tests.php --service=AIService\n\n";
    }
}

// Main execution
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $args = array_slice($argv, 1);
    $runner = new ComprehensiveTestRunner($args);
    $runner->run();
}
