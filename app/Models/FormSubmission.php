<?php

namespace App\Models;

use Webgoeroe\Core\Models\FormSubmission as CoreModel;

/**
 * Basis uit de package webgoeroe/core. Deze site heeft naast het
 * contactformulier ook een boekingsformulier (App\Livewire\Forms\BookingForm).
 */
class FormSubmission extends CoreModel
{
    /**
     * Labels per formuliertype (admin, e-mailonderwerp en de leads van
     * webgoeroe/seo-growth). Alfabetisch.
     */
    public const TYPE_LABELS = [
        'booking' => 'Boekingsaanvraag',
        'contact' => 'Contactformulier',
    ];
}
