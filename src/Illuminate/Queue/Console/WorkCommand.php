<?php namespace Illuminate\Queue\Console; use Illuminate\Queue\Worker; use Illuminate\Queue\Jobs\Job; use Illuminate\Console\Command; use Symfony\Component\Console\Input\InputOption; use Symfony\Component\Console\Input\InputArgument; use Symfony\Component\Console\Output\OutputInterface; class WorkCommand extends Command { protected $name = 'queue:work'; protected $description = 'Process the next job on a queue'; protected $worker; public function __construct(Worker $worker) { parent::__construct(); $this->worker = $worker; } public function fire() { if ($this->downForMaintenance()) return; $queue = $this->option('queue'); $delay = $this->option('delay'); $memory = $this->option('memory'); $connection = $this->argument('connection'); $response = $this->worker->pop( $connection, $queue, $delay, $memory, $this->option('sleep'), $this->option('tries') ); if ( ! is_null($response['job'])) { $this->writeOutput($response['job'], $response['failed']); } } protected function writeOutput(Job $job, $failed) { $options = OutputInterface::OUTPUT_RAW; if ($failed) { $this->output->writeln('<error>Failed:</error> '.$job->getName(), $options); } else { $this->output->writeln('<info>Processed:</info> '.$job->getName(), $options); } } protected function downForMaintenance() { if ($this->option('force')) return false; return $this->laravel->isDownForMaintenance(); } protected function getArguments() { return array( array('connection', InputArgument::OPTIONAL, 'The name of connection', null), ); } protected function getOptions() { return array( array('queue', null, InputOption::VALUE_OPTIONAL, 'The queue to listen on'), array('delay', null, InputOption::VALUE_OPTIONAL, 'Amount of time to delay failed jobs', 0), array('force', null, InputOption::VALUE_NONE, 'Force the worker to run even in maintenance mode'), array('memory', null, InputOption::VALUE_OPTIONAL, 'The memory limit in megabytes', 128), array('sleep', null, InputOption::VALUE_OPTIONAL, 'Number of seconds to sleep when no job is available', 3), array('tries', null, InputOption::VALUE_OPTIONAL, 'Number of times to attempt a job before logging it failed', 0), ); } }
