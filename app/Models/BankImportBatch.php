<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_account_id',
        'file_name',
        'file_hash',
        'period_start',
        'period_end',
        'row_count',
        'new_count',
        'changed_count',
        'unchanged_count',
        'missing_count',
        'strategy',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'row_count' => 'integer',
            'new_count' => 'integer',
            'changed_count' => 'integer',
            'unchanged_count' => 'integer',
            'missing_count' => 'integer',
        ];
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function transactions()
    {
        return $this->hasMany(BankTransaction::class, 'import_batch_id');
    }
}
