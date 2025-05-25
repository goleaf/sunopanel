<?php

declare(strict_types=1);

use App\Models\Genre;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->genre = Genre::factory()->create([
        'name' => 'City Pop',
        'genre_id' => 'city-pop-123',
    ]);
});

describe('Genre Model', function () {
    it('can be created with valid data', function () {
        expect($this->genre)->toBeInstanceOf(Genre::class)
            ->and($this->genre->name)->toBe('City Pop')
            ->and($this->genre->genre_id)->toBe('city-pop-123');
    });

    it('has correct fillable attributes', function () {
        $fillable = ['name', 'slug', 'genre_id'];
        expect($this->genre->getFillable())->toEqual($fillable);
    });

    it('automatically generates slug when creating', function () {
        $genre = Genre::create(['name' => 'Lo-Fi Hip Hop']);
        expect($genre->slug)->toBe('lo-fi-hip-hop');
    });

    it('does not override existing slug', function () {
        $genre = Genre::create([
            'name' => 'City Pop',
            'slug' => 'custom-slug',
        ]);
        expect($genre->slug)->toBe('custom-slug');
    });
});

describe('Genre Relationships', function () {
    it('belongs to many tracks', function () {
        $track1 = Track::factory()->create(['title' => 'Track 1']);
        $track2 = Track::factory()->create(['title' => 'Track 2']);

        $this->genre->tracks()->attach([$track1->id, $track2->id]);

        expect($this->genre->tracks)->toHaveCount(2)
            ->and($this->genre->tracks->first()->title)->toBe('Track 1')
            ->and($this->genre->tracks->last()->title)->toBe('Track 2');
    });
});

describe('Genre Scopes', function () {
    beforeEach(function () {
        $genreWithTracks = Genre::factory()->create(['name' => 'With Tracks']);
        $genreWithoutTracks = Genre::factory()->create(['name' => 'Without Tracks']);
        
        $track = Track::factory()->create();
        $genreWithTracks->tracks()->attach($track->id);
    });

    it('can filter genres with tracks', function () {
        $genresWithTracks = Genre::withTracks()->get();
        expect($genresWithTracks)->toHaveCount(1)
            ->and($genresWithTracks->first()->name)->toBe('With Tracks');
    });

    it('can order genres by name', function () {
        Genre::factory()->create(['name' => 'Zebra Genre']);
        Genre::factory()->create(['name' => 'Alpha Genre']);

        $orderedGenres = Genre::orderByName()->get();
        expect($orderedGenres->first()->name)->toBe('Alpha Genre')
            ->and($orderedGenres->last()->name)->toBe('Zebra Genre');
    });
}); 