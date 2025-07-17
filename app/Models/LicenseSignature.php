<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseSignature extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'license_template_id',
        'signature_text',
        'signature_data',
        'signature_method',
        'ip_address',
        'user_agent',
        'agreement_hash',
        'metadata',
        'is_verified',
        'verified_at',
        'verified_by',
        'status',
        'revocation_reason',
        'revoked_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    // ========== RELATIONSHIPS ==========

    /**
     * Get the project this signature belongs to
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who signed
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the license template that was signed
     */
    public function licenseTemplate(): BelongsTo
    {
        return $this->belongsTo(LicenseTemplate::class);
    }

    /**
     * Get the user who verified this signature
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ========== SCOPES ==========

    /**
     * Scope to get active signatures
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get verified signatures
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    // ========== METHODS ==========

    /**
     * Check if the signature is valid
     */
    public function isValid(): bool
    {
        return $this->status === 'active' &&
               ! empty($this->agreement_hash) &&
               (! empty($this->signature_text) || ! empty($this->signature_data));
    }

    /**
     * Revoke this signature
     */
    public function revoke(string $reason, ?User $revokedBy = null): void
    {
        $this->update([
            'status' => 'revoked',
            'revocation_reason' => $reason,
            'revoked_at' => now(),
            'verified_by' => $revokedBy?->id,
        ]);
    }

    /**
     * Verify this signature
     */
    public function verify(User $verifiedBy): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => $verifiedBy->id,
        ]);
    }

    /**
     * Get signature display name
     */
    public function getDisplaySignature(): string
    {
        if ($this->signature_method === 'canvas' && ! empty($this->signature_data)) {
            return 'Digital Signature';
        }

        return $this->signature_text ?? 'Electronic Signature';
    }

    /**
     * Generate audit trail entry
     */
    public function getAuditInfo(): array
    {
        return [
            'signed_at' => $this->created_at,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'method' => $this->signature_method,
            'verified' => $this->is_verified,
            'status' => $this->status,
        ];
    }

    /**
     * Create signature from project and user
     */
    public static function createFromProject(
        Project $project,
        User $user,
        array $signatureData
    ): self {
        return self::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'license_template_id' => $project->license_template_id,
            'signature_text' => $signatureData['signature_text'] ?? null,
            'signature_data' => $signatureData['signature_data'] ?? null,
            'signature_method' => $signatureData['method'] ?? 'text',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'agreement_hash' => hash('sha256', $project->getLicenseContent()),
            'metadata' => $signatureData['metadata'] ?? [],
        ]);
    }
}
