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
        
        // Reload from database to ensure we have fresh data
        $this->genre = Genre::find($this->genre->id);
        $this->genre->load('tracks');

        expect($this->genre->tracks)->toHaveCount(2);
        
        $trackTitles = $this->genre->tracks->pluck('title')->sort()->values();
        expect($trackTitles->toArray())->toBe(['Track 1', 'Track 2']);
    });
});

describe('Genre Scopes', function () {
    beforeEach(function () {
        $genreWithTracks = Genre::factory()->withName('With Tracks')->create();
        $genreWithoutTracks = Genre::factory()->withName('Without Tracks')->create();
        
        $track = Track::factory()->create();
        $genreWithTracks->tracks()->attach($track->id);
    });

    it('can filter genres with tracks', function () {
        // Reload data to ensure it's properly persisted
        $genresWithTracks = Genre::has('tracks')->get();
        expect($genresWithTracks)->toHaveCount(1);
        
        if ($genresWithTracks->count() > 0) {
            expect($genresWithTracks->first()->name)->toBe('With Tracks');
        }
    });

    it('can order genres by name', function () {
        Genre::factory()->withName('Zebra Genre')->create();
        Genre::factory()->withName('Alpha Genre')->create();

        $orderedGenres = Genre::orderByName()->get();
        expect($orderedGenres->first()->name)->toBe('Alpha Genre')
            ->and($orderedGenres->last()->name)->toBe('Zebra Genre');
    });
}); 