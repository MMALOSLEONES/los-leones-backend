<?php

// Limite HAUTE de chaque catégorie, en kg, de la plus légère à la plus lourde.
// Un poids appartient à la première catégorie dont la limite est supérieure ou égale.
//
// ⚠️ Valeurs usuelles du MMA (règles unifiées). À FAIRE VALIDER par l'organisateur local :
// c'est le SEUL endroit à modifier si les limites sont différentes. Le site, le
// dashboard et la validation des candidats les lisent tous ici.
return [
    'Strawweight' => 52.2,
    'Flyweight' => 56.7,
    'Bantamweight' => 61.2,
    'Featherweight' => 65.8,
    'Lightweight' => 70.3,
    'Welterweight' => 77.1,
    'Middleweight' => 83.9,
    'Light Heavyweight' => 93.0,
    'Heavyweight' => 120.2,
];
