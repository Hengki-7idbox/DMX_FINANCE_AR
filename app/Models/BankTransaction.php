<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_account_id',
        'import_batch_id',
        'baserow_id',
        'sequence_no',
        'document_number',
        'transaction_date',
        'is_paid',
        'description',
        'branch_code',
        'amount',
        'has_issue',
        'balance',
        'period_month',
        'period_year',
        'amount_text',
        'position',
        'document_internal',
        'document_external',
        'note',
        'status',
        'row_key',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'is_paid' => 'boolean',
            'has_issue' => 'boolean',
            'amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'baserow_id' => 'integer',
            'sequence_no' => 'integer',
            'period_month' => 'integer',
            'period_year' => 'integer',
        ];
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function importBatch()
    {
        return $this->belongsTo(BankImportBatch::class, 'import_batch_id');
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['bank_account_id'] ?? null, fn ($q, $v) => $q->where('bank_account_id', $v))
            ->when($filters['period_year'] ?? null, fn ($q, $v) => $q->where('period_year', $v))
            ->when($filters['period_month'] ?? null, fn ($q, $v) => $q->where('period_month', $v))
            ->when($filters['position'] ?? null, fn ($q, $v) => $q->where('position', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['q'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $like = '%' . $v . '%';
                    $sub->where('description', 'like', $like)
                        ->orWhere('document_number', 'like', $like)
                        ->orWhere('document_internal', 'like', $like)
                        ->orWhere('document_external', 'like', $like)
                        ->orWhere('note', 'like', $like);
                });
            });

        return $query;
    }
}
