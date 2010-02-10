#!/usr/bin/php
<?php
require 'microbenchmark.php';


function microbench_all_refl_classes(MicroBenchmark $bench) {
  $fh = fopen('all_reflectors.csv', 'w');
  $bench->function = 'class_onearg';
  $functions = array('reflext', 'reflclass', 'reflobj', 'reflfunc', );
  $means = array();
  foreach ($functions as $function) {
    $bench->determineInternalOffset();
    call_user_func_array('_run_bench_' . $function, array($fh, $bench, $means));
  }
  print_r($means);
  fclose($fh);
}

function _run_bench_reflext($fh, MicroBenchmark $bench, &$means) {
  $results = array();
  foreach (get_loaded_extensions() as $ext) {
    $bench->args = array('class' => 'ReflectionExtension', 'arg1' => $ext);
    $results[] = $bench->runBench(FALSE);
    fputcsv($fh, array_merge(array('reflector' => 'ReflectionExtension', 'ext' => $ext), $bench->getResults()));
  }
  $means['reflext'] = array_sum($results) / count($results);
}

function _run_bench_reflclass($fh, MicroBenchmark $bench, &$means) {
  $results = array();
  foreach (get_declared_classes() as $class) {
    $bench->args = array('class' => 'ReflectionClass', 'arg1' => $class);
    $results[] = $bench->runBench(FALSE);
    fputcsv($fh, array_merge(array('reflector' => 'ReflectionClass', 'class' => $class), $bench->getResults()));
  }
  $means['reflclass'] = array_sum($results) / count($results);
}

function _run_bench_reflobj($fh, MicroBenchmark $bench, &$means) {
  $results = array();
  $classes = array(
    'stdClass',
    'DateTime',
    'tidy',
    'tidyNode',
    'XMLReader',
    'XMLWriter',
    'SplObjectStorage',
    'Exception',
    'ErrorException',
    'LogicException',
    'SplTempFileObject'
  );
  foreach ($classes as $class) {
    $bench->args = array('class' => 'ReflectionObject', 'arg1' => new $class);
    $results[] = $bench->runBench(FALSE);
    fputcsv($fh, array_merge(array('reflector' => 'ReflectionObject', 'class' => $class), $bench->getResults()));
  }
  $means['reflobj'] = array_sum($results) / count($results);
}

function _run_bench_reflfunc($fh, MicroBenchmark $bench, &$means) {
  $results = array();
  $functions = array(
    'array_splice',
    'array_diff',
    'preg_match',
    'strstr',
    'get_declared_interfaces',
    'time',
  );
  foreach ($functions as $function) {
    $bench->args = array('class' => 'ReflectionFunction', 'arg1' => $function);
    $results[] = $bench->runBench(FALSE);
    fputcsv($fh, array_merge(array('reflector' => 'ReflectionFunction', 'function' => $function), $bench->getResults()));
  }
  $means['reflfunc'] = array_sum($results) / count($results);
}

function _run_bench_reflfuncabs($fh, MicroBenchmark $bench, &$means) {
  $results = array();
  $functions = array(
    'array_splice',
    'array_diff',
    'preg_match',
    'strstr',
    'get_declared_interfaces',
    'time',
  );
  foreach ($functions as $function) {
    $bench->args = array('class' => 'ReflectionFunctionAbstract', 'arg1' => $function);
    $results[] = $bench->runBench(FALSE);
    fputcsv($fh, array_merge(array('reflector' => 'ReflectionClass', 'function' => $function), $bench->getResults()));
  }
  $means['reflfuncabs'] = array_sum($results) / count($results);
}

function microbench_instanciate_classes(MicroBenchmark $bench) {
  $fh = fopen('objinstanciate.csv', 'w');
  $bench->determineInternalOffset();
  _run_bench_empties($fh, $bench);
  _run_bench_iterators($fh, $bench);
  fclose($fh);
}

function _run_bench_empties($fh, MicroBenchmark $bench) {
  $bench->function = 'class_noargs';
  $classes = array(
    'stdClass',
    'DateTime',
    'tidy',
    'tidyNode',
    'XMLReader',
    'XMLWriter',
    'SplObjectStorage',
    'Exception',
    'ErrorException',
    'LogicException',
    'SplTempFileObject'
  );
  foreach ($classes as $class) {
    $bench->args = array('class' => $class);
    $bench->runBench(FALSE);
    fputcsv($fh, array_merge(array('class' => $class), $bench->getResults()));
  }
}

function _run_bench_iterators($fh, MicroBenchmark $bench) {
  $bench->function = 'class_onearg';
  foreach (array('InfiniteIterator', 'CachingIterator', 'IteratorIterator', 'NoRewindIterator', 'AppendIterator') as $class) {
    $bench->args = array('class' => $class, 'arg1' => new EmptyIterator());
    $bench->runBench(FALSE);
    fputcsv($fh, array_merge(array('class' => $class), $bench->getResults()));
  }
}

function microbench_refl_comparisons(MicroBenchmark $bench) {
  $bench->function = 'drb';
  $bench->args = array('class' => 'FunctionBenchmark');
  $refloffset = $bench->runBench();

  $results = array();
  $functions = array(
    '_do_refl_instanciate',
    '_do_proc_instanciate',
    '_do_refl_interfaces',
    '_do_proc_interfaces',
    '_do_refl_methodexists',
    '_do_proc_methodexists',
    'do_reflection_bench',
    'do_procedural_bench',
  );
  $fh = fopen('refl_comparisons.csv', 'w');
  foreach ($functions as $func) {
    $bench->manualOffset = strstr($func, 'refl') ? $refloffset : 0;
    $bench->function = $func;
    $bench->runBench(FALSE);
    $results[$func] = $bench->getResults();
    fputcsv($fh, array_merge(array('func' => $func), $bench->getResults()));
  }
  $bench->manualOffset = 0;
  print_r($results);
}

function microbench_generic($bench, $function, $args) {
  $bench->function = $function;
  $bench->args = $args;
  $bench->runBench();
  print_r($bench->getResults());
}

$bench = new FunctionBenchmark();
$bench->covar = 0.02;
$bench->tries = 25;
date_default_timezone_set('America/Chicago');
// microbench_all_refl_classes($bench);
//microbench_all_classes_refl($bench);
// microbench_instanciate_classes($bench);
// microbench_refl_comparisons($bench);
microbench_generic($bench, 'drb', array('class' => 'DirectoryIterator'));
microbench_generic($bench, 'drb_int', array('class' => 'DirectoryIterator'));

function drb_int($args) {
  $refl = new ReflectionClass($args['class']);
  $refl->getInterfaces();
}

// Benching functions

//$bench->function = 'arshift';
//$bench->args = array('class' => 'RecursiveDirectoryIterator');
//$bench->runBench();
//print_r($bench->getResults());
//
//function arshift($args) {
//  $class = array_shift($args);
//}

function do_reflection_bench($args) {
  $refl = new ReflectionClass($args['class']);
  if ($refl->implementsInterface('MicroBenchmark')) {
    $instance = $refl->newInstance();
  }
}
function do_procedural_bench($args) {
  $interfaces = class_implements($args['class']);
  if (isset($interfaces['MicroBenchmark'])) {
    $instance = new $args['class']();
  }
}

function drb($args) {
  $refl = new ReflectionClass($args['class']);
}

function _do_refl_interfaces() {
  $refl = new ReflectionClass('RecursiveDirectoryIterator');
  $refl->getInterfaceNames();
}
function _do_proc_interfaces() {
  class_implements('RecursiveDirectoryIterator');
}

function _do_refl_methodexists() {
  $refl = new ReflectionClass('RecursiveDirectoryIterator');
  $refl->hasMethod('next');
}
function _do_proc_methodexists() {
  method_exists('RecursiveDirectoryIterator', 'next');
}

function _do_refl_instanciate() {
  $refl = new ReflectionClass('RecursiveDirectoryIterator');
  $refl->newInstance('/tmp');
}
function _do_proc_instanciate() {
  new RecursiveDirectoryIterator('/tmp');
}

function class_noargs($args) {
  new $args['class']();
}

function class_onearg($args) {
  new $args['class']($args['arg1']);
}

// Test classes for instanciating
class A {

}

class B {
  public function __construct() {}
}