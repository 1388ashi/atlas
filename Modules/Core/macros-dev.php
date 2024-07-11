<?php

use Illuminate\Database\Schema\Blueprint;

Blueprint::macro('authors', function () {
    $this->foreignId('creator_id');
    $this->foreignId('updater_id');
});

Blueprint::macro('morphAuthors', function () {
    $this->morphs('creatorable');
    $this->morphs('updaterable');
});
