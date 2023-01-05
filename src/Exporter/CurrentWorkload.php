<?php


namespace LKDevelopment\HorizonPrometheusExporter\Exporter;


use Laravel\Horizon\Contracts\WorkloadRepository;
use LKDevelopment\HorizonPrometheusExporter\Contracts\Exporter;
use Prometheus\CollectorRegistry;

class CurrentWorkload implements Exporter
{
    protected $gauge;
    public function metrics(CollectorRegistry $collectorRegistry)
    {
        $this->gauge = $collectorRegistry->getOrRegisterGauge(
            config('horizon-exporter.namespace'),
            'horizon_current_workload',
            'Current workload of all queues',
            ['queue']
        );
    }

    public function collect()
    {
        $workloadRepository = app(WorkloadRepository::class);
        $workloads = collect($workloadRepository->get())->sortBy('name')->values();

        $workloads->each(function ($workload) {
            if ($workload['split_queues']) {
                $workload['split_queues']->each(function ($queue) {
                    $this->gauge->set($queue['length'], [$queue['name']]);
                });

                return;
            }

            $this->gauge->set($workload['length'], [$workload['name']]);
        });
    }
}
