<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Réponses aux critères de la checklist lors d'une inspection
 */
class InspectionResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'inspection_id',
        'checklist_id',
        'value_boolean',
        'value_rating',
        'value_text',
        'comment',
        'media_files',
    ];

    protected $casts = [
        'value_boolean' => 'boolean',
        'value_rating' => 'integer',
        'media_files' => 'array',
    ];

    /**
     * Inspection concernée
     */
    public function inspection()
    {
        return $this->belongsTo(Inspection::class);
    }

    /**
     * Critère de la checklist
     */
    public function checklist()
    {
        return $this->belongsTo(InspectionChecklist::class, 'checklist_id');
    }
}

