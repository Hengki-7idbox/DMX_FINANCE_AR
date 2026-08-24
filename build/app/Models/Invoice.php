<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number', 'customer_id', 'invoice_date', 'due_date',
        'amount', 'currency', 'status', 'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function collectionActions()
    {
        return $this->hasMany(CollectionAction::class);
    }

    public function exclusion()
    {
        return $this->hasOne(InvoiceExclusion::class, 'invoice_number', 'invoice_number');
    }

    // Scopes
    public function scopeOutstanding($query)
    {
        return $query->whereIn('status', ['PENDING', 'SENT', 'PARTIAL']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())->outstanding();
    }

    public function scopeByAgingBucket($query, string $bucket)
    {
        return match($bucket) {
            'current' => $query->whereRaw('DATEDIFF(CURDATE(), due_date) BETWEEN 0 AND 30'),
            '31-60' => $query->whereRaw('DATEDIFF(CURDATE(), due_date) BETWEEN 31 AND 60'),
            '61-90' => $query->whereRaw('DATEDIFF(CURDATE(), due_date) BETWEEN 61 AND 90'),
            'over90' => $query->whereRaw('DATEDIFF(CURDATE(), due_date) > 90'),
            default => $query,
        };
    }

    // Helpers
    public function getDaysOverdue(): int
    {
        if ($this->due_date->isFuture()) return 0;
        return (int) $this->due_date->diffInDays(now());
    }

    public function getAgingBucket(): string
    {
        $days = $this->getDaysOverdue();
        return match(true) {
            $days <= 30 => 'current',
            $days <= 60 => '31-60',
            $days <= 90 => '61-90',
            default => 'over90',
        };
    }

    public function getAmountPaid(): float
    {
        return (float) $this->payments->sum('amount');
    }

    public function getBalance(): float
    {
        return (float) $this->amount - $this->getAmountPaid();
    }
}
