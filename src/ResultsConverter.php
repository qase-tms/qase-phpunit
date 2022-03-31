<?php

declare(strict_types=1);

namespace Qase\PHPUnit;

use Qase\Client\Model\ResultCreate;

class ResultsConverter
{
    private const SUITE_TITLE_SEPARATOR = "\t";

    private ConsoleLogger $logger;

    public function __construct(ConsoleLogger $logger)
    {
        $this->logger = $logger;
    }

    public function prepareBulkResults(RunResult $runResult, string $rootSuiteTitle): array
    {
        $bulkResults = [];
        $addedForSending = [];

        foreach ($runResult->getResults() as $item) {
            $resultData = [
                'status' => $item['status'],
                'timeMs' => (int)($item['time'] * 1000.0),
                'stacktrace' => $item['stacktrace'],
                'comment' => "{$item['full_test_name']} in {$item['time']}s",
            ];

            [$namespace, $methodName] = $this->explodeFullTestName($item['full_test_name']);

            $caseTitle = $this->clearPrefix($methodName, ['test']);
            $suiteTitle = $rootSuiteTitle . self::SUITE_TITLE_SEPARATOR .
                str_replace('\\', self::SUITE_TITLE_SEPARATOR, $this->clearPrefix($namespace, ['Test\\', 'Tests\\']));

            $caseId = $this->getCaseIdFromAnnotation($namespace, $methodName);
            if ($caseId) {
                $resultData['caseId'] = $caseId;
            } else {
                $resultData['case'] = [
                    'title' => $caseTitle,
                    'suite_title' => $suiteTitle,
                    'automation' => 2,
                ];

            }

            if (preg_match('/with data set "(.+)"|(#\d+)/U', $item['full_test_name'], $paramMatches, PREG_UNMATCHED_AS_NULL) === 1) {
                $resultData['param'] = ['phpunit' => $paramMatches[1] ?? $paramMatches[2]];
            }

            $bulkResults[] = new ResultCreate($resultData);
            $addedForSending[$suiteTitle][] = $caseTitle;
        }

        foreach ($addedForSending as $suiteTitle => $caseTitles) {
            foreach (array_unique($caseTitles) as $caseTitle) {
                $suiteTitle = str_replace(self::SUITE_TITLE_SEPARATOR, '\\', $suiteTitle);
                $this->logger->writeln("[added for sending]'{$suiteTitle}' '{$caseTitle}'");
            }
        }

        return $bulkResults;
    }

    private function getCaseIdFromAnnotation(string $namespace, string $methodName): ?int
    {
        $reflection = new \ReflectionMethod($namespace, $methodName);

        $docComment = $reflection->getDocComment();
        if (!$docComment || !preg_match_all('/\@qaseId?\s+(?P<caseId>.*?)\s*\r?$/m', $docComment, $qaseIdMatches)) {
            return null;
        }

        if (!($qaseIdMatches['caseId'][0] ?? false)) {
            return null;
        }

        return (int)$qaseIdMatches['caseId'][0] ?: null;
    }

    private function explodeFullTestName($fullTestName): array
    {
        if (!preg_match_all('/(?P<namespace>.+)::(?P<methodName>\w+)/', $fullTestName, $testNameMatches)) {
            $this->logger->writeln("WARNING: Could not parse test name '{$fullTestName}'");
            throw new \RuntimeException('Could not parse test name');
        }

        return [$testNameMatches['namespace'][0], $testNameMatches['methodName'][0]];
    }

    /**
     * @param string $title
     * @param array[string] $prefixes
     * @return string
     */
    private function clearPrefix(string $title, array $prefixes): string
    {
        foreach ($prefixes as $prefix) {
            $prefixLength = mb_strlen($prefix);
            if (strncmp($title, $prefix, $prefixLength) === 0) {
                return mb_substr($title, $prefixLength);
            }
        }

        return $title;
    }
}
