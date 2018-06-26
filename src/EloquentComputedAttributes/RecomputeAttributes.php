<?php

namespace SehrGut\EloquentComputedAttributes;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use SehrGut\EloquentComputedAttributes\Contracts\Recomputable;

class RecomputeAttributes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The model/object that should be recomputed.
     *
     * @var Recomputable
     */
    protected $recomputable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Recomputable $recomputable)
    {
        $this->recomputable = $recomputable;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->recomputable->recompute();
        $this->recomputable->save();
    }
}
