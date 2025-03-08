<?php

namespace App\Exceptions\Pitch;

/**
 * Exception thrown when there's an error related to pitch snapshots
 */
class SnapshotException extends PitchException
{
    /**
     * @var int|null
     */
    protected $snapshotId;
    
    /**
     * The HTTP status code for this exception
     *
     * @var int
     */
    protected $statusCode = 422;
    
    /**
     * Create a new instance of the exception
     *
     * @param int|null $snapshotId The ID of the snapshot
     * @param string $message The error message
     * @param int $code The error code
     * @param \Throwable|null $previous The previous exception
     */
    public function __construct(
        ?int $snapshotId = null,
        string $message = "Snapshot operation failed",
        int $code = 0,
        \Throwable $previous = null
    ) {
        $this->snapshotId = $snapshotId;
        
        if ($snapshotId) {
            $message .= " for snapshot #$snapshotId";
        }
        
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Get the snapshot ID
     *
     * @return int|null
     */
    public function getSnapshotId()
    {
        return $this->snapshotId;
    }
}
