<?php

interface MicroBenchmark {
  public function __construct();
  public function runBench($get_internal = TRUE);
  public function getResults();
  public function getInternalOffset();
  public function determineInternalOffset();
}

class FunctionBenchmark implements MicroBenchmark {
  /**
   * The function to be run during benchmark iterations.
   * @var string
   */
  public $function = '';
  public $args = array();
  protected $iterations;
  public $tries = 5;
  public $sampleSize = 30;
  public $covar = 0.01;
  protected $internalOffset;
  public $manualOffset = 0.000;
  protected $results = array();

  /**
   * Create a new MicroBenchmark instance.
   *
   * The number of iterations is set here, as an argument to the constructor,
   * because internal offset calculations are all dependent the number of
   * iterations being fixed. Forcing the iterations to be set once in the
   * constructor simplifies other logic later.
   *
   * @param int $iterations
   *   The number of times the benchmarked code will be iterated per sample.
   */
  public function __construct($iterations = 100000) {
    $this->iterations = $iterations;
  }

  /**
   * Public-facing wrapper method for calling the microbenchmarker externally.
   *
   * Takes no arguments; internal object state is used instead.
   *
   * @return float
   *   The fully adjusted mean for this benchmark.
   */
  public function runBench($get_internal = TRUE) {
    if (!function_exists($this->function)) {
      throw new Exception('The function specified for microbenchmarking does not exist.', E_ERROR);
    }

    if ($get_internal) {
      $this->determineInternalOffset();
    }

    print "Benching function $this->function\n";

    return $this->run($this->function, $this->args, $this->sampleSize);
  }

  /**
   * Perform the microbenchmark, including setup, post-processing, and
   * repetition (if needed).
   *
   * Takes params instead of using properties so that this code can be reused
   * for internal offset calculations.
   *
   * @param callback $function
   * @param array $args
   * @param int $sample_size
   * @param int $iterations
   * @param float $offset
   * @return float
   */
  protected function run($function, $args, $sample_size, $try = 0) {
    $this->results = $result = array();
    if (++$try > $this->tries) {
      // Bail out after $this->try number of failed to attempts to achieve
      // results that are within the specified coefficient of variance.
      return -1;
    }

    $this->results = array(
      'attempts' => $try,
      'internal offset' => $this->internalOffset,
      'manual offset' => $this->manualOffset,
    );

    // Run the benchmark.
    $results = array();
    for ($s = 0; $s < $sample_size; $s++) {
      // Pass number of iterations as a local variable, because accessing a
      // property during the loop is slow and introduces too much variance
      $results[] = $this->benchmark($function, $args, $this->iterations);
    }

    // Perform result set processing
    $this->results += self::processResultSet($results);
    $this->results['internally adjusted mean'] = $this->results['raw mean'] - $this->internalOffset;
    $this->results['fully adjusted mean'] = $this->results['internally adjusted mean'] - $this->manualOffset;
    if ($this->results['covar'] > $this->covar) {
      // If coefficient of variance is greater than the specified allowable
      // variance, try the benchmarking run again.
      return $this->run($function, $args, $sample_size, $try);
    }
    return $this->results['fully adjusted mean'];
  }

  /**
   * Perform the benchmark itself.
   *
   * Inherits arguments from FunctionBenchmark::run().
   */
  protected function benchmark($function, $args, $iterations) {
    $start = microtime(TRUE);
    for ($i = 0; $i < $iterations; ++$i) {
      $function($args);
    }
    $end = microtime(TRUE);
    return $end - $start;
  }

  /**
   * Process results into a raw mean, standard deviation and coefficient of
   * variance.
   *
   * @param array $result
   *  An indexed array of microtime(TRUE)-derived time differentials.
   * @return array
   */
  public static function processResultSet($result) {
    // find the mean
    $mean = array_sum($result) / count($result);
    // calculate the standard deviation
    $powsum = 0;
    foreach ($result as $val) {
      $powsum += pow($val - $mean, 2);
    }
    $stdev = $powsum / count($result);
    // calculate coefficient of variance
    $covar = $stdev / $mean;
    return array(
      'covar' => $covar,
      'stdev' => $stdev,
      'raw mean' => $mean,
    );
  }

  /**
   * Returns the current result set, if any.
   *
   * @return array
   */
  public function getResults($print = FALSE) {
    if ($print) print_r($this->results);
    else return $this->results;
  }

  /**
   * Run a mockup microbenchmark to isolate the processing overhead incurred by
   * the framework itself.
   *
   * @return float
   */
  public function determineInternalOffset() {
    $this->internalOffset = 0;
    $orig_covar = $this->covar;
    $this->covar = 0.005; // demand coefficient of variance be <0.5%
    $this->run('microbench_stub', $this->args, 100);
    $this->internalOffset = $this->results['raw mean'];
    $this->covar = $orig_covar;
  }

  public function getInternalOffset() {
    if (empty($this->internalOffset)) {
      $this->determineInternalOffset();
    }
    return $this->internalOffset;
  }
}

/**
 * Stub function, used by MicroBenchmark::getMyOffset() to estimate the
 * internal overhead incurred by the dynamic call to a userspace function.
 *
 */
function microbench_stub() {}
