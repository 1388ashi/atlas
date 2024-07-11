<?php

return [
    'model' => \Modules\Comment\Entities\Comment::class,
    'commented_models' => [
        'posts' => \Modules\Blog\Entities\Post::class
    ]
];
