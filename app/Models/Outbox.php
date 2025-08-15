<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\OutboxAggregateType;
use App\Enums\OutboxEventType;
use InvalidArgumentException;
use Illuminate\Support\Str;

class Outbox extends Model
{
    protected $table = 'outbox';

    protected $keyType = 'string';
    public $incrementing = false;


    protected $fillable = [
        'aggregate_type',
        'aggregate_id',
        'event_type',
        'payload',
        'occurred_at',
    ];

    protected $casts = [
        'aggregate_type' => OutboxAggregateType::class,
        'event_type' => OutboxEventType::class,
        'payload' => 'array',
        'attempts' => 'integer',
        'occurred_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'reserved_at' => 'datetime',
        'next_attempt_at' => 'datetime',
    ];


    
    // Validate event type belongs to the correct aggregate type 
    // Event types and aggregate types are already linked in the enums
    protected static function booted(){
        static::creating(function (Outbox $model) {
            if(empty($model->occurred_at)) {
                $model->occurred_at = now();
            }

           $model->assertEventMatchesAggregate();

           if(empty($model->getKey())){
              $model->{$model->getKeyName()} = (string) Str::uuid();
           };
        });

        static::updating(function (Outbox $model) {
            // Guard against negative attempts
            if ($model->attempts !== null && $model->attempts < 0) {
                $model->attempts = 0;
            }

            $model->assertEventMatchesAggregate();
        });
    }

    protected function assertEventMatchesAggregate()
    {   

        // If either aggregate type or event type is missing, skip
        if (! $this->aggregate_type || ! $this->event_type) {
            return;
        }

        // Allowed event types are defined on the aggregate type enum itself
        $allowed = $this->aggregate_type->allowedEventTypes();

        // Check if the event type is allowed for the aggregate type
        if(!in_array($this->event_type, $allowed, true)) {
            throw new InvalidArgumentException(
                "Event type {$this->event_type->value} is not allowed for aggregate type {$this->aggregate_type->value}"
            );
        }
    }

    // ============= Scopes ============ //

    public function scopePending($query)
    {
        return $query->whereNull('dispatched_at');
    }

    public function scopeDue($query){
        return $query->where(function ($q) {
            $q->whereNull('next_attempt_at')
            ->orWhere('next_attempt_at', '<=', now());
        });
    }

    public function scopeReservable($query, int $graceSeconds = 60) {
        return $query->where(function ($q) use ($graceSeconds) {
            $q->whereNull('reserved_at')
            ->orWhere('reserved_at', '<', now()->subSeconds($graceSeconds));
        });
    }

    public function scopeForAggregate($query, OutboxAggregateType $type, string $id)
    {
        return $query->where('aggregate_type', $type)
            ->where('aggregate_id', $id);
    }

    public function scopeForEvent($query, OutboxEventType $type)
    {
        return $query->where('event_type', $type);
    }

    // ============= Helpers ============ //

    public function reserve(string $workerId, int $leaseSeconds = 60): void
    {
        $this->reserved_by = $workerId;
        $this->reserved_at = now();
        $this->next_attempt_at = now()->addSeconds($leaseSeconds);
        $this->save();
    }

    public function markDispatched(){
        $this->dispatched_at = now();
        $this->reserved_at = null; // Clear reservation
        $this->reserved_by = null; // Clear worker ID
        $this->save();
    }

    public function failWithBackoff(string $errorMessage, int $baseSeconds = 60, int $maxSeconds = 3600){
        $this->attempts++;
        $delay = min($maxSeconds, $baseSeconds * (2 ** max(0, $this->attempts - 1)));
        $this->next_attempt_at = now()->addSeconds($delay);
        $this->error = Str::limit($errorMessage, 1000);
        $this->reserved_at = null; // Clear reservation
        $this->reserved_by = null; // Clear worker ID
        $this->save();
    }
}
