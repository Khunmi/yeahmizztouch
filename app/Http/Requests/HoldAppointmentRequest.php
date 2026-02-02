<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PhotoConsent;
use App\Models\PolicySetting;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HoldAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    public function rules(): array
    {
        $minimumAge = PolicySetting::getValue('minimum_client_age', 15);
        $maxDate = CarbonImmutable::now()->subYears($minimumAge)->format('Y-m-d');

        return [
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'starts_at' => ['required', 'date', 'after:now'],
            
            'client.first_name' => ['required', 'string', 'max:50'],
            'client.last_name' => ['required', 'string', 'max:50'],
            'client.email' => ['required', 'email', 'max:255'],
            'client.phone' => ['required', 'string', 'max:20'],
            'client.date_of_birth' => ['required', 'date', "before_or_equal:{$maxDate}"],
            
            'photo_consent' => ['required', Rule::enum(PhotoConsent::class)],
            'policy_acknowledged' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        $minimumAge = PolicySetting::getValue('minimum_client_age', 15);

        return [
            'client.date_of_birth.before_or_equal' => "Client must be at least {$minimumAge} years old.",
            'policy_acknowledged.accepted' => 'You must acknowledge the salon policies to proceed.',
        ];
    }
}
