<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BloqueCompteRequest extends FormRequest
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
            'jours_blocage' => 'required|integer|min:1|max:365',
            // 'date_blocage' => 'required|date|after:today',
            'date_blocage' => 'required|date',
            'motif_blocage' => 'required|string|max:255',
        ];


    }

    public function messages()
    {
        return [
            'jours_blocage.required' => 'Le nombre de jours de blocage est obligatoire.',
            'jours_blocage.integer' => 'Le nombre de jours de blocage doit être un entier.',
            'jours_blocage.min' => 'Le nombre de jours de blocage doit être au moins de 1 jour.',
            'jours_blocage.max' => 'Le nombre de jours de blocage ne peut pas dépasser 365 jours.',
            'date_blocage.required' => 'La date de blocage est obligatoire.',
            'date_blocage.date' => 'La date de blocage doit être une date valide.',
            // 'date_blocage.after' => 'La date de blocage doit être une date future.',
            'motif_blocage.required' => 'Le motif de blocage est obligatoire.',
            'motif_blocage.string' => 'Le motif de blocage doit être une chaîne de caractères.',
            'motif_blocage.max' => 'Le motif de blocage ne peut pas dépasser 255 caractères.',
        ];
    }
}
