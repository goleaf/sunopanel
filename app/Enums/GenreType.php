<?php

namespace App\Enums;

enum GenreType: string
{
    case BUBBLEGUM_BASS = 'Bubblegum bass';
    case CHILLWAVE = 'Chillwave';
    case DRUM_AND_BASS = 'Drum and bass';
    case DUBSTEP = 'Dubstep';
    case EDM = 'EDM';
    case ELECTROHOUSE = 'Electrohouse';
    case FOLK = 'Folk';
    case FRENCHCORE = 'Frenchcore';
    case GLITCH = 'Glitch';
    case GLITCHY = 'Glitchy';
    case GRIME = 'Grime';
    case HARDSTYLE = 'Hardstyle';
    case HYPNOTIC_TRANCE = 'Hypnotic trance';
    case KPOP = 'K-pop';
    case LOFI = 'Lo-fi';
    case POP = 'Pop';
    case RAVE = 'Rave';
    case RINGTONE = 'Ringtone';
    case SKA = 'Ska';
    case SOUTHERN_ROCK = 'Southern rock';
    case SYMPHONIC_METAL = 'Symphonic metal';
    case TECH_HOUSE = 'Tech-house';
    case TRAP = 'Trap';
    case URBAN_POP = 'Urban-Pop';

    public static function fromName(string $name): self
    {
        $name = trim($name);
        $formattedName = ucfirst(strtolower($name));

        foreach (self::cases() as $case) {
            if (strtolower($case->value) === strtolower($formattedName)) {
                return $case;
            }
        }

        throw new \ValueError("\"$name\" is not a valid backing value for enum ".self::class);
    }

    public static function tryFromName(string $name): ?self
    {
        try {
            return self::fromName($name);
        } catch (\ValueError $e) {
            return null;
        }
    }
}
