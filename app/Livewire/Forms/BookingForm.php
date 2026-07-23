<?php

namespace App\Livewire\Forms;

use App\Livewire\Concerns\PersistsLocale;
use App\Mail\FormSubmissionMail;
use App\Models\FormSubmission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Boekingsformulier — de primaire conversie. Vangt meteen de info voor een
 * offerte op maat (datum, locatie, type gelegenheid, aantal gasten). Slaat op in
 * `form_submissions` (type 'booking'), mailt de eigenaar en toont een bevestiging.
 *
 * Zelfde flow als ContactForm; enkel meer velden. Geregistreerd op vier plekken:
 * deze component, de form_type-dropdown (FormFields), FormSubmission::TYPE_LABELS
 * en de match() in de form-partial.
 */
class BookingForm extends Component
{
    use PersistsLocale;

    protected string $type = 'booking';

    #[Validate('required|string|max:120')]
    public string $name = '';

    #[Validate('required|email|max:190')]
    public string $email = '';

    #[Validate('required|string|max:40')]
    public string $phone = '';

    #[Validate('nullable|date')]
    public string $event_date = '';

    #[Validate('nullable|string|max:160')]
    public string $location = '';

    #[Validate('required|string|max:40')]
    public string $event_type = '';

    #[Validate('nullable|string|max:40')]
    public string $guests = '';

    #[Validate('nullable|string|max:5000')]
    public string $message = '';

    /** Honeypot: bots vullen dit in, mensen zien het niet. */
    public string $website = '';

    public bool $sent = false;

    /**
     * Type-opties — alfabetisch, met "Anders" bewust als laatste (semantisch
     * sterker dan strikt alfabetisch: "overige gevallen" hoort achteraan).
     *
     * @return array<string, string>
     */
    public function eventTypes(): array
    {
        return [
            'bruiloft' => __('Bruiloft'),
            'clubavond' => __('Clubavond'),
            'festival' => __('Festival / strandfeest'),
            'privefeest' => __('Privéfeest'),
            'anders' => __('Anders'),
        ];
    }

    /** @return array<string, string> Nederlandse validatieberichten. */
    protected function messages(): array
    {
        return [
            'name.required' => __('Vul je naam in.'),
            'email.required' => __('Vul je e-mailadres in.'),
            'email.email' => __('Vul een geldig e-mailadres in.'),
            'phone.required' => __('Vul een telefoonnummer in zodat we snel kunnen schakelen.'),
            'event_type.required' => __('Kies het type gelegenheid.'),
            'event_date.date' => __('Vul een geldige datum in.'),
        ];
    }

    public function submit(): void
    {
        // Stille spam-afhandeling: doe alsof het lukte, sla niets op.
        if ($this->website !== '') {
            $this->sent = true;

            return;
        }

        $data = $this->validate();
        unset($data['website']);

        // Sla het leesbare type-label op i.p.v. de slug (bv. "Bruiloft").
        $data['event_type'] = $this->eventTypes()[$this->event_type] ?? $this->event_type;

        $submission = FormSubmission::create([
            'type' => $this->type,
            'data' => $data,
            'page_url' => url()->previous(),
            'ip' => request()->ip(),
        ]);

        $this->notifyOwner($submission);

        $this->sent = true;
        $this->reset(['name', 'email', 'phone', 'event_date', 'location', 'event_type', 'guests', 'message']);
    }

    protected function notifyOwner(FormSubmission $submission): void
    {
        $to = config('mail.from.address');

        if (blank($to)) {
            return;
        }

        try {
            Mail::to($to)->send(new FormSubmissionMail($submission));
        } catch (\Throwable $e) {
            Log::warning('Boeking-notificatie e-mail faalde: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.forms.booking-form');
    }
}
