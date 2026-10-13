<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Http\Requests\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->checkAuthorization(Auth::user(), ['user.edit']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user');

        return ld_apply_filters('user.update.validation.rules', [
           'external_name' => 'required|string|max:50',
            'email' => 'required|max:100|email|unique:users,email,'.$userId,
             'internal_name' => 'required|string|max:50',
            'password' => $userId ? 'nullable|min:6|confirmed' : 'required|min:6|confirmed',
            'sip_username' => [
                'nullable',
                'string',
                'max:64',
                'required_with:sip_password',
                Rule::unique('sip_credentials', 'sip_username')->ignore($userId, 'user_id'),
            ],
            'sip_password' => 'nullable|string|min:4',
            'dialer_provider' => 'required|in:freeswitch,vicidial',
            'vicidial_agent_user' => [
                'nullable', 'string', 'max:64', 'required_if:dialer_provider,vicidial', 'required_with:vici_username,vici_password',
                Rule::unique('vicidial_credentials', 'agent_user')->ignore($userId, 'user_id'),
            ],
            'vici_username' => 'nullable|string|max:64|required_if:dialer_provider,vicidial|required_with:vicidial_agent_user,vici_password',
            'vici_password' => [
                'nullable', 'string', 'min:4', 'max:255',
                Rule::requiredIf(fn () => ($this->input('dialer_provider') === 'vicidial' || $this->filled('vici_username') || $this->filled('vicidial_agent_user'))
                    && ! \App\Models\User::find($userId)?->vicidialCredential?->sip_password),
            ],
        ], $userId);
    }
}
