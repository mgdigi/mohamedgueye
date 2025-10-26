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
        return [
            'type' => 'required|string|in:epargne,cheque',
            'solde' => 'required|numeric|min:10000',
            'devise' => 'required|string|in:FCFA,EUR,USD',
            'user' => 'required|array',
            'user.nom' => 'required|string|max:255',
            'user.prenom' => 'required|string|max:255',
            'user.password' => 'required|string|min:6',
            'user.email' => 'required|email|unique:users,email',
            'user.telephone' => ['required', 'unique:users,telephone', new \App\Rules\ValidatePhone],
            'user.nci' => ['required', 'unique:users,nci', new \App\Rules\ValidateCni],
            'user.adresse' => 'required|string'
        ];
    }


        public function messages()
    {
        return [
            'solde.min' => 'Le solde initial doit être supérieur ou égal à 10 000 FCFA.',
            'user.email.unique' => 'Cet email est déjà utilisé.',
            'user.telephone.unique' => 'Ce numéro de téléphone existe déjà.',
            'user.nci.unique' => 'Ce numéro de CNI existe déjà.',
            'type.in' => 'Le type de compte doit être soit "epargne" soit "cheque".',
            'devise.in' => 'La devise doit être soit "FCFA", "EUR" ou "USD".',
            
        ];
    }
}
