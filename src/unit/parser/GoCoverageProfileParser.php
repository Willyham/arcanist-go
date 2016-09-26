<?php

final class GoCoverageProfileParser {

  private $resultSets = array();

  public function __construct($coverage) {
    $lines = explode("\n", $coverage);
    // Remove the 'mode' line from the beginning
    array_shift($lines);
    $parsedLines = array();
    foreach ($lines as $line) {
      $parsedLine = $this->parseLine($line);
      if (!is_null($parsedLine)) {
        array_push($parsedLines, $parsedLine);
      }
    }

    // Group the parsed lines by file
    foreach ($parsedLines as $coverageResult) {
      if (!array_key_exists($coverageResult->file, $this->resultSets)) {
        $this->resultSets[$coverageResult->file] = new CoverageResultSet();
      }
      $this->resultSets[$coverageResult->file]->addResult($coverageResult);
    }
  }

  public function generateStrings() {
    $output = array();
    foreach ($this->resultSets as $file => $resultSet) {
      var_dump($resultSet);
      $output[$file] = $this->generateString($resultSet);
    }
    return $output;
  }

  private function generateString($resultSet) {
    $output = '';
    $lines = $resultSet->getNumberOfLines();
    for ($line = 1; $line <= $lines; $line++) {
      $output .= $resultSet->getArcanistCharacter($line);
    }
    return $output;
  }

  private function parseLine($line) {
    $split = explode(':', $line);
    if (count($split) < 2) {
      return NULL;
    }
    $file = $split[0];
    $rest = $split[1];

    // Get the details of start/end and coverage
    list($locations, $count, $isCovered) = explode(' ', $rest);

    // Now split the start/end.
    list($start, $end) = explode(',', $locations);

    // Ditch the coumns, we don't care.
    $start = (int)$start;
    $end = (int)$end;

    $result = new CoverageResult();
    $result->file = $file;
    $result->startLine = $start;
    $result->endLine = $end;
    $result->count = $count;
    $result->isCovered = (bool)$isCovered;
    return $result;
  }

}

final class CoverageResultSet {

  private $results = array();

  public function addResult($result) {
    array_push($this->results, $result);
    var_dump($this->results);
  }

  public function getNumberOfLines() {
    $maxSeen = 0;
    foreach ($this->results as $result) {
      $maxSeen = max($maxSeen, $result->endLine);
    }
    return $maxSeen;
  }

  public function getArcanistCharacter($lineNum) {
    $coveredIn = NULL;
    // Find the result which contains this line
    foreach ($this->results as $i => $result) {
      if ($result->isWithinCoverageRange($lineNum)) {
        $coveredIn = $result;
      }
    }
    if (is_null($coveredIn)) {
      return CoverageResult::NOT_EXECUTABLE;
    }
    if ($coveredIn->isLineCovered($lineNum)) {
      return CoverageResult::COVERED;
    }
    return CoverageResult::UNCOVERED;
  }

}

final class CoverageResult {
  public $file;
  public $startLine;
  public $endLine;
  public $count;
  public $isCovered;

  const NOT_EXECUTABLE = 'N';
  const COVERED = 'C';
  const UNCOVERED = 'U';

  public function isWithinCoverageRange($lineNum) {
    return $lineNum >= $this->startLine && $lineNum <= $this->endLine;
  }

  public function isLineCovered($lineNum) {
    if (!$this->isWithinCoverageRange($lineNum)) {
      return false;
    }
    return $this->isCovered;
  }
}

?>
