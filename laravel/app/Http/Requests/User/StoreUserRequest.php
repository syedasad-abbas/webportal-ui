<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Http\Requests\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->checkAuthorization(Auth::user(), ['user.create']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ld_apply_filters('user.store.validation.rules', [
            'external_name' => 'required|string|max:255',
            'email' => 'required|max:100|email|unique:users,email',
              'internal_name' => 'required|string|max:255|unique:users,internal_name',
            'password' => 'required|min:6|confirmed',
            'sip_username' => 'nullable|string|max:64|unique:sip_credentials,sip_username|required_with:sip_password',
            'sip_password' => 'nullable|string|min:4|required_with:sip_username',
            'dialer_provider' => 'required|in:freeswitch,vicidial',
            'vicidial_agent_user' => 'nullable|string|max:64|required_if:dialer_provider,vicidial|required_with:vici_username,vici_password|unique:vicidial_credentials,agent_user',
            'vici_username' => 'nullable|string|max:64|required_if:dialer_provider,vicidial|required_with:vicidial_agent_user,vici_password',
            'vici_password' => 'nullable|string|min:4|max:255|required_if:dialer_provider,vicidial|required_with:vicidial_agent_user,vici_username',
        ]);
    }
}
