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
        'client' => 'required|array',
        'client.nom' => 'required|string|max:255',
        'client.prenom' => 'required|string|max:255',
        'client.password' => 'required|string|min:6',
        'client.email' => 'required|email|unique:users,email',
        'client.telephone' => [
            'required',
            'unique:users,telephone',
            new \App\Rules\ValidatePhone
        ],
        'client.nci' => [
            'required',
            'unique:users,nci',
            new \App\Rules\ValidateCni
        ],
        'client.adresse' => 'required|string'
    ];
}



    public function messages()
    {
        return [
            'type.required' => 'Le type de compte est obligatoire.',
            'type.string' => 'Le type de compte doit être une chaîne de caractères.',
            'type.in' => 'Le type de compte doit être soit "epargne" soit "cheque".',
            'solde.required' => 'Le solde initial est obligatoire.',
            'solde.numeric' => 'Le solde doit être un nombre.',
            'solde.min' => 'Le solde initial doit être supérieur ou égal à 10 000 FCFA.',
            'devise.required' => 'La devise est obligatoire.',
            'devise.string' => 'La devise doit être une chaîne de caractères.',
            'devise.in' => 'La devise doit être soit "FCFA", "EUR" ou "USD".',
            'client.required' => 'Les informations du client sont obligatoires.',
            'client.array' => 'Les informations du client doivent être un tableau.',
            'client.nom.required' => 'Le nom du client est obligatoire.',
            'client.nom.string' => 'Le nom doit être une chaîne de caractères.',
            'client.nom.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'client.prenom.required' => 'Le prénom du client est obligatoire.',
            'client.prenom.string' => 'Le prénom doit être une chaîne de caractères.',
            'client.prenom.max' => 'Le prénom ne peut pas dépasser 255 caractères.',
            'client.password.required' => 'Le mot de passe est obligatoire.',
            'client.password.string' => 'Le mot de passe doit être une chaîne de caractères.',
            'client.password.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
            'client.email.required' => 'L\'adresse email est obligatoire.',
            'client.email.email' => 'L\'adresse email doit être valide.',
            'client.email.unique' => 'Cet email est déjà utilisé.',
            'client.telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'client.telephone.unique' => 'Ce numéro de téléphone existe déjà.',
            'client.nci.required' => 'Le numéro CNI est obligatoire.',
            'client.nci.unique' => 'Ce numéro de CNI existe déjà.',
            'client.adresse.required' => 'L\'adresse est obligatoire.',
            'client.adresse.string' => 'L\'adresse doit être une chaîne de caractères.'
        ];
    }
}
