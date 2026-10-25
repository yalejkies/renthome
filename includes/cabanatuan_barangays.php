<?php
declare(strict_types=1);

/**
 * StayFinder location scope: Cabanatuan City, Nueva Ecija.
 * Barangay names supplied for the project filter.
 */
const CABANATUAN_BARANGAYS = [
    'Aduas Centro',
    'Aduas Norte',
    'Aduas Sur',
    'Bagong Sikat',
    'Bakal I',
    'Bakal II',
    'Bakal III',
    'Bangad',
    'Barrera',
    'Bitas',
    'Bonifacio',
    'Burol',
    'Calawagan',
    'Caudillo',
    'Communal',
    'Daan Sarile',
    'Dalampang',
    'Dau',
    'Del Rosario',
    'Diaz',
    'DS Garcia',
    'Fatima',
    'General Luna',
    'Imelda',
    'Isla',
    'Kapt Pepe',
    'Mabini Extension',
    'Mabini Homesite',
    'Macatbong',
    'Magtanggol',
    'Magsaysay Norte',
    'Magsaysay Sur',
    'Matadero',
    'Mayapyap Norte',
    'Mayapyap Sur',
    'Melojavilla',
    'Middle Calawagan',
    'Magsaysay District',
    'MS Garcia',
    'Nabao',
    'Narciso',
    'Pagas',
    'Palagay',
    'Palayan',
    'Pamaldan',
    'Pangatian',
    'Patalac',
    'Polilio',
    'Pula',
    'Quezon District',
    'Rizdelis',
    'Samon',
    'San Isidro',
    'San Josef Norte',
    'San Josef Sur',
    'San Juan Accfa',
    'San Juan Poblacion',
    'San Roque Norte',
    'San Roque Sur',
    'Sanbermicristi',
    'Sangitan',
    'Sangitan East',
    'Santa Arcadia',
    'Santo Niño',
    'Sapang',
    'Sumacab Este',
    'Sumacab Norte',
    'Sumacab South',
    'Talipapa',
    'Valdefuente',
    'Valle Cruz',
    'Vijandre District',
    'Villa Ofelia-Caridad',
    'Zulueta District',
];

function cabanatuan_barangays(): array
{
    return CABANATUAN_BARANGAYS;
}

function is_valid_cabanatuan_barangay(string $location): bool
{
    return in_array($location, CABANATUAN_BARANGAYS, true);
}
