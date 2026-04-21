<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;
use SendKit\Exceptions\SendKitException;
use SendKit\Laravel\Facades\SendKit;

class EmailRealValidation implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (config('mail.default') !== 'sendkit' || ! config('app.enable_email_enhanced_verification')) {
            return;
        }

        try {
            $result = SendKit::validateEmail((string) $value);
        } catch (SendKitException $e) {
            Log::info('Email verification failed', [
                'email' => $value,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if (! data_get($result, 'should_block', false)) {
            return;
        }

        Log::info('Email verification failed', [
            'email' => $value,
            'result' => $result,
        ]);

        $fail(__('validation.email', ['attribute' => $attribute]));
    }
}
