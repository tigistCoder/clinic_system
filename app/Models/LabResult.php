<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'lab_request_id',
        'test_name',
        'result_value',
        'remarks',
        'technician_id',
    ];

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function labRequest()
    {
        return $this->belongsTo(LabRequest::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
