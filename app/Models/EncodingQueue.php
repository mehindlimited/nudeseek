<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EncodingQueue extends Model
{
    use HasFactory;

    protected $table = 'encoding_queue';

    protected $fillable = [
        'video_code',
        'status',
        'input_file_path',
        'output_file_path',
        'thumbnail_paths',
        'encoding_options',
        'error_message',
        'retry_count',
        'max_retries',
        'last_retry_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'encoding_options' => 'array',
        'thumbnail_paths' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_retry_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => Carbon::now(),
        ]);
    }

    public function markAsCompleted(string $outputPath, array $thumbnailPaths = []): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'output_file_path' => $outputPath,
            'thumbnail_paths' => $thumbnailPaths,
            'completed_at' => Carbon::now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'completed_at' => Carbon::now(),
        ]);
    }

    public function incrementRetry(string $errorMessage = null): void
    {
        $this->increment('retry_count');
        $this->update([
            'status' => self::STATUS_PENDING,
            'error_message' => $errorMessage,
            'last_retry_at' => Carbon::now(),
        ]);
    }

    public function canRetry(): bool
    {
        return $this->retry_count < $this->max_retries;
    }

    public function shouldRetry(): bool
    {
        return $this->status === self::STATUS_FAILED && $this->canRetry();
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function scopeRetryable($query)
    {
        return $query->where('status', self::STATUS_FAILED)
            ->whereColumn('retry_count', '<', 'max_retries');
    }

    public function scopeStuck($query, $minutes = 30)
    {
        return $query->where('status', self::STATUS_PROCESSING)
            ->where('started_at', '<', Carbon::now()->subMinutes($minutes));
    }
}
