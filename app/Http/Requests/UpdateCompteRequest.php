<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'nom' => 'sometimes|string|max:50',
            'prenom' => 'sometimes|string|max:70',
            'email' => 'sometimes|email|unique:users,email,' . $this->route('id'),
            'telephone' => 'sometimes|string|max:20|unique:users,telephone,' . $this->route('id'),
            'adresse' => 'sometimes|string|max:150',
            'nci' => 'sometimes|string|max:20|unique:users,nci,' . $this->route('id'),
        ];
    }

    public function messages()
    {
        return [
            'email.unique' => 'Cet email est déjà utilisé.',
            'telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'nci.unique' => 'Ce NCI est déjà utilisé.',
        ];
    }

    protected function prepareForValidation()
    {
        if (!$this->filled(['nom','prenom','email','telephone','adresse','nci'])) {
            $this->merge(['at_least_one' => null]);
        }
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->filled(['nom','prenom','email','telephone','adresse','nci'])) {
                $validator->errors()->add('at_least_one', 'Au moins un champ doit être renseigné pour la modification.');
            }
        });
    }
}
