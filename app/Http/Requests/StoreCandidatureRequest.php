<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCandidatureRequest extends FormRequest
{
    // Route publique : n'importe quel visiteur peut candidater
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $compteur = ['nullable', 'integer', 'min:0', 'max:999'];

        return [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'tel' => ['required', 'regex:/^\d{9}$/'],
            // Majeur : né il y a 18 ans ou plus
            'date_naissance' => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->toDateString()],
            'mail' => ['nullable', 'email', 'max:255'],
            'ville' => ['required', 'string', 'max:255'],
            'quartier' => ['nullable', 'string', 'max:255'],

            'a_experience_combat' => ['required', 'boolean'],
            'experience_niveau' => ['nullable', 'required_if:a_experience_combat,1', 'in:debutant,intermediaire,avance,professionnel'],
            'a_fait_competitions' => ['nullable', 'boolean'],
            'nombre_combats' => $compteur,
            'victoires' => $compteur,
            'defaites' => $compteur,
            'nuls' => $compteur,
            'kos' => $compteur,
            'soumissions' => $compteur,

            'comment_connu' => ['nullable', 'in:instagram,tiktok,facebook,whatsapp,recommandation,google,autre'],
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5 Mo
            'consent_accepted' => ['accepted'],
            // "status" n'est volontairement PAS listé : un visiteur ne décide jamais du statut
        ];
    }

    // Règles de cohérence entre plusieurs champs
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $combats = (int) $this->input('nombre_combats', 0);
                $victoires = (int) $this->input('victoires', 0);
                $defaites = (int) $this->input('defaites', 0);
                $nuls = (int) $this->input('nuls', 0);
                $kos = (int) $this->input('kos', 0);
                $soumissions = (int) $this->input('soumissions', 0);

                if ($victoires + $defaites + $nuls > $combats) {
                    $validator->errors()->add('victoires', 'Victoires + défaites + nuls ne peuvent pas dépasser le nombre de combats.');
                }
                if ($kos + $soumissions > $victoires) {
                    $validator->errors()->add('kos', 'KO + soumissions ne peuvent pas dépasser le nombre de victoires.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Le champ :attribute est obligatoire.',
            'required_if' => 'Le champ :attribute est obligatoire.',
            'tel.regex' => 'Le téléphone doit contenir exactement 9 chiffres.',
            'date_naissance.before_or_equal' => 'Tu dois avoir au moins 18 ans pour candidater.',
            'date' => 'Le champ :attribute n\'est pas une date valide.',
            'mail.email' => 'L\'adresse email est invalide.',
            'integer' => 'Le champ :attribute doit être un nombre entier.',
            'min' => 'Le champ :attribute ne peut pas être négatif.',
            'max' => 'Le champ :attribute est trop grand.',
            'in' => 'La valeur du champ :attribute est invalide.',
            'photo.required' => 'La photo est obligatoire.',
            'photo.mimes' => 'La photo doit être au format JPG, PNG ou WebP.',
            'photo.max' => 'La photo ne doit pas dépasser 5 Mo.',
            'consent_accepted.accepted' => 'Le consentement est obligatoire.',
        ];
    }

    public function attributes(): array
    {
        return [
            'tel' => 'téléphone',
            'prenom' => 'prénom',
            'date_naissance' => 'date de naissance',
            'mail' => 'email',
            'a_experience_combat' => 'sport de combat',
            'experience_niveau' => 'niveau',
            'nombre_combats' => 'nombre de combats',
            'comment_connu' => 'source',
        ];
    }
}
