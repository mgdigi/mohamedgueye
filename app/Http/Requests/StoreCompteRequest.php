<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
{
    $email = $this->input('client.email');
    $telephone = $this->input('client.telephone');
    $nci = $this->input('client.nci');

    $existingUser = \App\Models\User::where('email', $email)
        ->orWhere('telephone', $telephone)
        ->orWhere('nci', $nci)
        ->first();

    return [
        'type' => 'required|string|in:epargne,cheque',
        'solde' => 'required|numeric|min:10000',
        'devise' => 'required|string|in:FCFA,EUR,USD',
        'client' => 'required|array',
        'client.nom' => 'required|string|max:255',
        'client.prenom' => 'required|string|max:255',
        'client.password' => 'required|string|min:6',
        'client.email' => [
            'required', 'email',
            $existingUser ? '' : 'unique:users,email',
        ],
        'client.telephone' => [
            'required', new \App\Rules\ValidatePhone,
            $existingUser ? '' : 'unique:users,telephone',
        ],
        'client.nci' => [
            'required', new \App\Rules\ValidateCni,
            $existingUser ? '' : 'unique:users,nci',
        ],
        'client.adresse' => 'required|string'
    ];
}



        public function messages()
    {
        return [
            'solde.min' => 'Le solde initial doit être supérieur ou égal à 10 000 FCFA.',
            'client.email.unique' => 'Cet email est déjà utilisé.',
            'client.telephone.unique' => 'Ce numéro de téléphone existe déjà.',
            'client.nci.unique' => 'Ce numéro de CNI existe déjà.',
            'type.in' => 'Le type de compte doit être soit "epargne" soit "cheque".',
            'devise.in' => 'La devise doit être soit "FCFA", "EUR" ou "USD".',
            
        ];
    }
}
